## About
A PHP Wrapper for the <a href="https://tms-demo.ccvdev.eu/api/external/index.html">CCV - Estate Management - Terminal Management</a>

## Installation
`composer require pinvandaag/p-market-api-php`

## Small usage example

```php
<?php

use Dotenv\Dotenv;
use PinVandaag\TmsCcvAPI\TmsCcvAPIClient;

final class PMarketController
{
    private PMarketAPIClient $apiClient;

    private \Nette\Database\Explorer $database_dashboard;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . "/../../../../");
        $dotenv->safeLoad();
        $dotenv->required(['P_MARKET_API_URL', 'P_MARKET_API_KEY', 'P_MARKET_API_SECRET'])->notEmpty();

        $this->apiClient = (new PMarketAPIClient())
            ->configure(
                apiUrl: $_ENV['P_MARKET_API_URL'],
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