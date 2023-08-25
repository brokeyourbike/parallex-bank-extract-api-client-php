<?php

// Copyright (C) 2023 Ivan Stasiuk <ivan@stasi.uk>.
//
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this file,
// You can obtain one at https://mozilla.org/MPL/2.0/.

namespace BrokeYourBike\ParallexBankExtract\Responses;

use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\Casters\ArrayCaster;
use Spatie\DataTransferObject\Attributes\MapFrom;
use Spatie\DataTransferObject\Attributes\CastWith;
use BrokeYourBike\DataTransferObject\JsonResponse;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 */
class AccountsResponse extends JsonResponse
{
    public string $responseCode;
    public string $responseDescription;

    /** @var \BrokeYourBike\ParallexBankExtract\Responses\Account[] */
    #[CastWith(ArrayCaster::class, Account::class)]
    #[MapFrom('data')]
    public ?array $accounts;
}

class Account extends DataTransferObject
{
    #[MapFrom('accountNumber')]
    public string $number;

    #[MapFrom('accountName')]
    public string $name;
}
