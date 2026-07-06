## About
A PHP Wrapper for the <a href="https://github.com/PAXSTORE/paxstore-openapi-java-sdk/blob/master/docs/TERMINAL_API.md">PAX Technology  p-market-api</a>

## Installation
`composer require pinvandaag/p-market-api-php`

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