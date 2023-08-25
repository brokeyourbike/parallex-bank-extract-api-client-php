<?php

// Copyright (C) 2023 Ivan Stasiuk <ivan@stasi.uk>.
//
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this file,
// You can obtain one at https://mozilla.org/MPL/2.0/.

namespace BrokeYourBike\ParallexBankExtract;

use Psr\SimpleCache\CacheInterface;
use GuzzleHttp\ClientInterface;
use Carbon\Carbon;
use BrokeYourBike\ResolveUri\ResolveUriTrait;
use BrokeYourBike\ParallexBankExtract\Responses\LoginResponse;
use BrokeYourBike\ParallexBankExtract\Responses\InterBankPaymentResponse;
use BrokeYourBike\ParallexBankExtract\Interfaces\TransactionInterface;
use BrokeYourBike\ParallexBankExtract\Interfaces\ConfigInterface;
use BrokeYourBike\HttpEnums\HttpMethodEnum;
use BrokeYourBike\HttpClient\HttpClientTrait;
use BrokeYourBike\HttpClient\HttpClientInterface;
use BrokeYourBike\HasSourceModel\SourceModelInterface;
use BrokeYourBike\HasSourceModel\HasSourceModelTrait;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 */
class Client implements HttpClientInterface
{
    use HttpClientTrait;
    use ResolveUriTrait;
    use HasSourceModelTrait;

    private ConfigInterface $config;
    private CacheInterface $cache;

    public function __construct(ConfigInterface $config, ClientInterface $httpClient, CacheInterface $cache)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->cache = $cache;
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function authTokenCacheKey(): string
    {
        return get_class($this) . ':authToken:';
    }

    public function getAuthToken(): ?string
    {
        if ($this->cache->has($this->authTokenCacheKey())) {
            $cachedToken = $this->cache->get($this->authTokenCacheKey());

            if (is_string($cachedToken)) {
                return $cachedToken;
            }
        }

        $response = $this->fetchAuthTokenRaw();

        if ($response->token === null) {
            return $response->token;
        }

        $expireAt = Carbon::parse($response->expiration);

        $this->cache->set(
            $this->authTokenCacheKey(),
            $response->token,
            (Carbon::now())->diffInSeconds($expireAt) - 60
        );

        return $response->token;
    }

    public function fetchAuthTokenRaw(): LoginResponse
    {
        $options = [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
            \GuzzleHttp\RequestOptions::JSON => [
                'username' => $this->config->getUsername(),
                'password' => $this->config->getPassword(),
            ],
        ];

        $uri = (string) $this->resolveUriFor($this->config->getUrl(), 'api/authentication/Authentication/Login');
        $response = $this->httpClient->request(HttpMethodEnum::POST->value, $uri, $options);
        return new LoginResponse($response);
    }

    public function interBankPayment(TransactionInterface $transaction): InterBankPaymentResponse
    {
        $options = [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$this->getAuthToken()}",
            ],
            \GuzzleHttp\RequestOptions::JSON => [
                'sourceAccountNumber' => $this->config->getDebitAccountNumber(),
                'nameEnquirySessionID' => $transaction->getReference(),
                'narration' => $transaction->getReference(),
                'location' => '6.451140,3.388400',
                'amount' => (string) $transaction->getAmount(),
                'beneficiary' => [
                    'accountName' => $transaction->getRecipientName(),
                    'accountNumber' => $transaction->getAccountNumber(),
                    'institutionCode' => $transaction->getBankCode(),
                    'institutionName' => $transaction->getBankName(),
                    'bvn' => '00000000000',
                    'kycLevel' => 'TIER3',
                ],
            ],
        ];

        if ($transaction instanceof SourceModelInterface){
            $options[\BrokeYourBike\HasSourceModel\Enums\RequestOptions::SOURCE_MODEL] = $transaction;
        }

        $uri = (string) $this->resolveUriFor($this->config->getUrl(), 'api/IMTOService/InterBankTransfer');
        $response = $this->httpClient->request(HttpMethodEnum::POST->value, $uri, $options);
        return new InterBankPaymentResponse($response);
    }
}
