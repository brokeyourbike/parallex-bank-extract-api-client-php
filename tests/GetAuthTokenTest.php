<?php

// Copyright (C) 2023 Ivan Stasiuk <ivan@stasi.uk>.
//
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this file,
// You can obtain one at https://mozilla.org/MPL/2.0/.

namespace BrokeYourBike\ParallexBankExtract\Tests;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface;
use Carbon\Carbon;
use BrokeYourBike\ParallexBankExtract\Interfaces\ConfigInterface;
use BrokeYourBike\ParallexBankExtract\Client;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 */
class GetAuthTokenTest extends TestCase
{
    private string $tokenValue = 'super-secure-token';

    /** @test */
    public function it_can_cache_and_return_auth_token()
    {
        $mockedConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();

        $expirationTimeFormatted = Carbon::now()->addMinutes(2)->format('Y-m-d\TH:i:s\Z'); // 120 seconds

        $mockedResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $mockedResponse->method('getStatusCode')->willReturn(200);
        $mockedResponse->method('getBody')
            ->willReturn('{
                "responseCode": "00",
                "responseMessage": "OK",
                "token": "' . $this->tokenValue . '",
                "expiration": "' . $expirationTimeFormatted . '"
            }');

        /** @var \GuzzleHttp\Client $mockedClient */
        $mockedClient = $this->createPartialMock(\GuzzleHttp\Client::class, ['request']);
        $mockedClient->method('request')->willReturn($mockedResponse);

        /** @var \Mockery\MockInterface $mockedCache */
        $mockedCache = \Mockery::spy(CacheInterface::class);

        /**
         * @var ConfigInterface $mockedConfig
         * @var \GuzzleHttp\Client $mockedClient
         * @var CacheInterface $mockedCache
         * */
        $api = new Client($mockedConfig, $mockedClient, $mockedCache);
        $requestResult = $api->getAuthToken();

        $this->assertSame($this->tokenValue, $requestResult);

        /** @var \Mockery\MockInterface $mockedCache */
        $mockedCache->shouldHaveReceived('set')
            ->once()
            ->with(
                $api->authTokenCacheKey(),
                $this->tokenValue,
                120 - 60 - 1 // 60 is a fixed value, and 1 for the time of processing
            );
    }

    /** @test */
    public function it_can_return_cached_value()
    {
        $mockedConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();

        /** @var \Mockery\MockInterface $mockedClient */
        $mockedClient = \Mockery::mock(\GuzzleHttp\Client::class);
        $mockedClient->shouldNotReceive('request');

        /** @var \Mockery\MockInterface $mockedCache */
        $mockedCache = \Mockery::mock(CacheInterface::class);
        $mockedCache->shouldReceive('has')
            ->once()
            ->andReturn(true);
        $mockedCache->shouldReceive('get')
            ->once()
            ->andReturn($this->tokenValue);

        /**
         * @var ConfigInterface $mockedConfig
         * @var \GuzzleHttp\Client $mockedClient
         * @var CacheInterface $mockedCache
         * */
        $api = new Client($mockedConfig, $mockedClient, $mockedCache);
        $requestResult = $api->getAuthToken();

        $this->assertSame($this->tokenValue, $requestResult);
    }

    /** @test */
    public function it_will_throw_if_response_invalid()
    {
        $mockedConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();

        $mockedResponse = $this->getMockBuilder(\Psr\Http\Message\ResponseInterface::class)->getMock();
        $mockedResponse->method('getStatusCode')->willReturn(200);
        $mockedResponse->method('getBody')->willReturn('{}');

        /** @var \GuzzleHttp\Client $mockedClient */
        $mockedClient = $this->createPartialMock(\GuzzleHttp\Client::class, ['request']);
        $mockedClient->method('request')->willReturn($mockedResponse);

        /** @var \Mockery\MockInterface $mockedCache */
        $mockedCache = \Mockery::spy(CacheInterface::class);

        /**
         * @var ConfigInterface $mockedConfig
         * @var \GuzzleHttp\Client $mockedClient
         * @var CacheInterface $mockedCache
         * */
        $api = new Client($mockedConfig, $mockedClient, $mockedCache);

        try {
            $api->getAuthToken();
        } catch (\Throwable $th) {
            $this->assertInstanceOf(\TypeError::class, $th);

            /** @var \Mockery\MockInterface $mockedCache */
            $mockedCache->shouldNotReceive('set');

            return;
        }

        $this->fail('Exception was not thrown');
    }
}
