# parallex-bank-extract-api-client

[![Latest Stable Version](https://img.shields.io/github/v/release/brokeyourbike/parallex-bank-extract-api-client-php)](https://github.com/brokeyourbike/parallex-bank-extract-api-client-php/releases)
[![Total Downloads](https://poser.pugx.org/brokeyourbike/parallex-bank-extract-api-client/downloads)](https://packagist.org/packages/brokeyourbike/parallex-bank-extract-api-client)
[![Maintainability](https://api.codeclimate.com/v1/badges/89291066562555bc1352/maintainability)](https://codeclimate.com/github/brokeyourbike/parallex-bank-extract-api-client-php/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/89291066562555bc1352/test_coverage)](https://codeclimate.com/github/brokeyourbike/parallex-bank-extract-api-client-php/test_coverage)

Parallex Bank (Extract) API Client for PHP

## Installation

```bash
composer require brokeyourbike/parallex-bank-extract-api-client
```

## Usage

```php
use BrokeYourBike\ParallexBankExtract\Client;
use BrokeYourBike\ParallexBankExtract\Interfaces\ConfigInterface;

assert($config instanceof ConfigInterface);
assert($httpClient instanceof \GuzzleHttp\ClientInterface);
assert($psrCache instanceof \Psr\SimpleCache\CacheInterface);

$apiClient = new Client($config, $httpClient, $psrCache);
$apiClient->getAuthToken();
```

## Authors
- [Ivan Stasiuk](https://github.com/brokeyourbike) | [Twitter](https://twitter.com/brokeyourbike) | [LinkedIn](https://www.linkedin.com/in/brokeyourbike) | [stasi.uk](https://stasi.uk)

## License
[Mozilla Public License v2.0](https://github.com/brokeyourbike/parallex-bank-extract-api-client-php/blob/main/LICENSE)
