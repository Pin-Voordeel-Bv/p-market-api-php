## About
A PHP Wrapper for the <a href="https://github.com/PAXSTORE/paxstore-openapi-java-sdk/blob/master/docs/TERMINAL_API.md">PAX Technology  p-market-api</a>

## Installation
`composer require pinvandaag/p-market-api-php`

## Implemented

- <a href="https://github.com/PAXSTORE/paxstore-openapi-java-sdk/blob/master/docs/ENTITY_ATTRIBUTE_API.md">EntityAttribute</a>
- <a href="https://github.com/PAXSTORE/paxstore-openapi-java-sdk/blob/master/docs/FACTORY_MODEL_API.md">FactoryModel</a>
- <a href="https://github.com/PAXSTORE/paxstore-openapi-java-sdk/blob/master/docs/MERCHANT_API.md">Merchant</a>
- <a href="https://github.com/PAXSTORE/paxstore-openapi-java-sdk/blob/master/docs/MERCHANT_CATEGORY_API.md">MerchantCategory</a>
- <a href="https://github.com/PAXSTORE/paxstore-openapi-java-sdk/blob/master/docs/RESELLER_API.md">Reseller</a>
- <a href="https://github.com/PAXSTORE/paxstore-openapi-java-sdk/blob/master/docs/TERMINAL_API.md">Terminal</a>
- <a href="https://github.com/PAXSTORE/paxstore-openapi-java-sdk/blob/master/docs/TERMINAL_APK_API.md">TerminalApk</a>
- <a href="https://github.com/PAXSTORE/paxstore-openapi-java-sdk/blob/master/docs/TERMINAL_FIRMWARE_API.md">TerminalFirmware</a>
- <a href="https://github.com/PAXSTORE/paxstore-openapi-java-sdk/blob/master/docs/TERMINAL_GROUP_API.md">TerminalGroup</a>

## Small usage example

```php
<?php

use Dotenv\Dotenv;
use PinVandaag\PMarketAPI\PMarketAPIClient;

final class PMarketController
{
    private PMarketAPIClient $apiClient;

    private \Nette\Database\Explorer $database_dashboard;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . "/../../../../");
        $dotenv->safeLoad();
        $dotenv->required(['P_MARKET_API_KEY', 'P_MARKET_API_SECRET'])->notEmpty();

        $this->apiClient = (new PMarketAPIClient())
            ->configure(
                // baseUri: 'https://api.whatspos.com/p-market-api',
                baseUri: 'https://api.store.ccv.eu/p-market-api',
                apiKey: $_ENV['P_MARKET_API_KEY'],
                apiSecret: $_ENV['P_MARKET_API_SECRET']
            );
    }

    public function getTerminal()
    {
        $this->jsonResponse([
            'data' => $this->apiClient->getTerminal(
                terminalId: (string) ($_GET['terminalId'] ?? ''),
            ),
        ]);
    }

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        echo json_encode($data, JSON_PRETTY_PRINT);
    }
}
```