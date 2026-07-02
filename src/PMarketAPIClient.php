<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI;

use GuzzleHttp\Client;
use PinVandaag\PMarketAPI\Client\APIClient;
use PinVandaag\PMarketAPI\Model\Terminal;
use Psr\Log\LoggerInterface;
use SensitiveParameter;

final class PMarketAPIClient
{
    private APIClient $apiClient;

    public function __construct(
        ?APIClient $apiClient = null,
        ?LoggerInterface $logger = null,
        ?string $baseUri = null,
    ) {
        $this->apiClient = $apiClient ?? new APIClient(new Client(), $baseUri ?? '');

        if ($logger !== null) {
            $this->apiClient->setLogger($logger);
        }
    }

    public function configure(
        #[SensitiveParameter] string $apiKey,
        ?string $baseUri = null,
    ): self {
        $this->apiClient->setApiKey($apiKey);

        if ($baseUri !== null) {
            $this->apiClient->setBaseUri($baseUri);
        }

        return $this;
    }

    /**
     * Retrieve a terminal by terminal ID.
     */
    public function getTerminal(
        string $terminalId,
    ): Terminal {
        return $this->apiClient->getTerminal($terminalId);
    }
}
