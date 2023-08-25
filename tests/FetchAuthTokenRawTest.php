<?php

// Copyright (C) 2023 Ivan Stasiuk <ivan@stasi.uk>.
//
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this file,
// You can obtain one at https://mozilla.org/MPL/2.0/.

namespace BrokeYourBike\ParallexBankExtract\Tests;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface;
use BrokeYourBike\ParallexBankExtract\Responses\LoginResponse;
use BrokeYourBike\ParallexBankExtract\Interfaces\ConfigInterface;
use BrokeYourBike\ParallexBankExtract\Client;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 */
class FetchAuthTokenRawTest extends TestCase
{
    private string $username = 'john';
    private string $password = 'secret';

    /** @test */
    public function it_can_prepare_request(): void
    {
        $mockedConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();
        $mockedConfig->method('getUrl')->willReturn('https://api.example/');
        $mockedConfig->method('getUsername')->willReturn($this->username);
        $mockedConfig->method('getPassword')->willReturn($this->password);

        $mockedResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $mockedResponse->method('getStatusCode')->willReturn(200);
        $mockedResponse->method('getBody')
            ->willReturn('{
                "responseCode": "00",
                "responseMessage": "OK",
                "token": "123456789",
                "expiration": "2023-08-16T22:20:54Z"
            }');

        /** @var \Mockery\MockInterface $mockedClient */
        $mockedClient = \Mockery::mock(\GuzzleHttp\Client::class);
        $mockedClient->shouldReceive('request')->withArgs([
            'POST',
            'https://api.example/api/authentication/Authentication/Login',
            [
                \GuzzleHttp\RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                ],
                \GuzzleHttp\RequestOptions::JSON => [
                    'username' => $this->username,
                    'password' => $this->password,
                ],
            ],
        ])->once()->andReturn($mockedResponse);

        $mockedCache = $this->getMockBuilder(CacheInterface::class)->getMock();

        /**
         * @var ConfigInterface $mockedConfig
         * @var \GuzzleHttp\Client $mockedClient
         * @var CacheInterface $mockedCache
         * */
        $api = new Client($mockedConfig, $mockedClient, $mockedCache);
        $requestResult = $api->fetchAuthTokenRaw();

        $this->assertInstanceOf(LoginResponse::class, $requestResult);
        $this->assertEquals('123456789', $requestResult->token);
    }
}
