<?php

// Copyright (C) 2023 Ivan Stasiuk <ivan@stasi.uk>.
//
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this file,
// You can obtain one at https://mozilla.org/MPL/2.0/.

namespace BrokeYourBike\ParallexBankExtract\Tests;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface;
use Carbon\CarbonImmutable;
use BrokeYourBike\ParallexBankExtract\Responses\InterBankPaymentResponse;
use BrokeYourBike\ParallexBankExtract\Interfaces\TransactionInterface;
use BrokeYourBike\ParallexBankExtract\Interfaces\ConfigInterface;
use BrokeYourBike\ParallexBankExtract\Client;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 */
class InterBankPaymentTest extends TestCase
{
    private string $token = 'super-secure-token';

    /** @test */
    public function it_can_prepare_request(): void
    {
        $transaction = $this->getMockBuilder(TransactionInterface::class)->getMock();
        $transaction->method('getReference')->willReturn('ref-123');
        $transaction->method('getBankCode')->willReturn('bank1');
        $transaction->method('getBankName')->willReturn('Bank1');
        $transaction->method('getAccountNumber')->willReturn('12345');
        $transaction->method('getRecipientName')->willReturn('John Doe');
        $transaction->method('getAmount')->willReturn(50.00);

        /** @var TransactionInterface $transaction */
        $this->assertInstanceOf(TransactionInterface::class, $transaction);

        $mockedConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();
        $mockedConfig->method('getUrl')->willReturn('https://api.example/');
        $mockedConfig->method('getDebitAccountNumber')->willReturn('debit-12345');

        $mockedResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $mockedResponse->method('getStatusCode')->willReturn(200);
        $mockedResponse->method('getBody')
            ->willReturn('{
                "responseCode":"00",
                "responseDescription":"Transaction Successful"
            }');

        /** @var \Mockery\MockInterface $mockedClient */
        $mockedClient = \Mockery::mock(\GuzzleHttp\Client::class);
        $mockedClient->shouldReceive('request')->withArgs([
            'POST',
            'https://api.example/api/IMTOService/InterBankTransfer',
            [
                \GuzzleHttp\RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer {$this->token}",
                ],
                \GuzzleHttp\RequestOptions::JSON => [
                    'sourceAccountNumber' => 'debit-12345',
                    'nameEnquirySessionID' => 'ref-123',
                    'narration' => 'ref-123',
                    'location' => '6.451140,3.388400',
                    'amount' => '50',
                    'beneficiary' => [
                        'accountName' => 'John Doe',
                        'accountNumber' => '12345',
                        'institutionCode' => 'bank1',
                        'institutionName' => 'Bank1',
                        'bvn' => '00000000000',
                        'kycLevel' => 'TIER3',
                    ],
                ],
            ],
        ])->once()->andReturn($mockedResponse);

        $mockedCache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $mockedCache->method('has')->willReturn(true);
        $mockedCache->method('get')->willReturn($this->token);

        /**
         * @var ConfigInterface $mockedConfig
         * @var \GuzzleHttp\Client $mockedClient
         * @var CacheInterface $mockedCache
         * */
        $api = new Client($mockedConfig, $mockedClient, $mockedCache);

        $requestResult = $api->interBankPayment($transaction);
        $this->assertInstanceOf(InterBankPaymentResponse::class, $requestResult);
    }
}
