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
        string $baseUri,
        #[SensitiveParameter] string $apiKey,
        #[SensitiveParameter] string $apiSecret,
    ): self {
        $this->apiClient
            ->setBaseUri($baseUri)
            ->setApiKey($apiKey)
            ->setApiSecret($apiSecret);

        return $this;
    }

    /**
     * Retrieve a terminal by terminal ID.
     */
    public function getTerminal(
        int|string $terminalId,
        bool $includeDetailInfoList = false,
        bool $includeInstalledApks = false,
        bool $includeInstalledFirmware = false,
        bool $includeMasterTerminal = false,
    ): Terminal {
        return $this->apiClient->getTerminal(
            $terminalId,
            $includeDetailInfoList,
            $includeInstalledApks,
            $includeInstalledFirmware,
            $includeMasterTerminal,
        );
    }
}
