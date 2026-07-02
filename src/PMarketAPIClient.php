<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI;

use GuzzleHttp\Client;
use PinVandaag\PMarketAPI\Client\APIClient;
use PinVandaag\PMarketAPI\Model\Terminal;
use PinVandaag\PMarketAPI\Model\TerminalCopyRequest;
use PinVandaag\PMarketAPI\Model\TerminalCreateRequest;
use PinVandaag\PMarketAPI\Model\TerminalSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalUpdateRequest;
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

    public function searchTerminal(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $status = null,
        ?string $snNameTID = null,
        ?string $resellerName = null,
        ?string $merchantName = null,
        bool $includeGeoLocation = false,
        bool $includeInstalledApks = false,
        bool $includeInstalledFirmware = false,
    ): TerminalSearchResult {
        return $this->apiClient->searchTerminal(
            $pageNo,
            $pageSize,
            $orderBy,
            $status,
            $snNameTID,
            $resellerName,
            $merchantName,
            $includeGeoLocation,
            $includeInstalledApks,
            $includeInstalledFirmware,
        );
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
 
    public function createTerminal(TerminalCreateRequest $terminalCreateRequest): Terminal
    {
        return $this->apiClient->createTerminal($terminalCreateRequest);
    }

    public function updateTerminal(int|string $terminalId, TerminalUpdateRequest $terminalUpdateRequest): Terminal
    {
        return $this->apiClient->updateTerminal($terminalId, $terminalUpdateRequest);
    }

    public function updateTerminalBySn(string $serialNo, TerminalUpdateRequest $terminalUpdateRequest): Terminal
    {
        return $this->apiClient->updateTerminalBySn($serialNo, $terminalUpdateRequest);
    }

    public function copyTerminal(TerminalCopyRequest $terminalCopyRequest): Terminal
    {
        return $this->apiClient->copyTerminal($terminalCopyRequest);
    }

    public function copyTerminalBySn(TerminalCopyRequest $terminalCopyRequest): Terminal
    {
        return $this->apiClient->copyTerminalBySn($terminalCopyRequest);
    }

    public function activateTerminal(int|string $terminalId): bool
    {
        return $this->apiClient->activateTerminal($terminalId);
    }

    public function activateTerminalBySn(string $serialNo): bool
    {
        return $this->apiClient->activateTerminalBySn($serialNo);
    }

    public function disableTerminal(int|string $terminalId): bool
    {
        return $this->apiClient->disableTerminal($terminalId);
    }

    public function disableTerminalBySn(string $serialNo): bool
    {
        return $this->apiClient->disableTerminalBySn($serialNo);
    }

    public function moveTerminal(int|string $terminalId, string $resellerName, string $merchantName): bool
    {
        return $this->apiClient->moveTerminal($terminalId, $resellerName, $merchantName);
    }

    public function moveTerminalBySn(string $serialNo, string $resellerName, string $merchantName): bool
    {
        return $this->apiClient->moveTerminalBySn($serialNo, $resellerName, $merchantName);
    }

    public function deleteTerminal(int|string $terminalId): bool
    {
        return $this->apiClient->deleteTerminal($terminalId);
    }

    public function deleteTerminalBySn(string $serialNo): bool
    {
        return $this->apiClient->deleteTerminalBySn($serialNo);
    }
}
