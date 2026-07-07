<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use DateTimeInterface;
use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\OptimizedParameterPushHistorySearchResult;
use PinVandaag\PMarketAPI\Model\ParameterPushHistorySearchResult;
use Psr\Http\Message\ResponseInterface;

trait PushHistoryApiTrait
{
    public function searchParameterPushHistory(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $packageName = null,
        ?string $serialNo = null,
        ?string $pushStatus = null,
        DateTimeInterface|string|null $pushTime = null,
    ): ParameterPushHistorySearchResult {
        return $this->searchParameterPushHistoryInternal(
            latest: false,
            optimized: false,
            pageNo: $pageNo,
            pageSize: $pageSize,
            packageName: $packageName,
            serialNo: $serialNo,
            pushStatus: $pushStatus,
            pushTime: $pushTime,
        );
    }

    public function searchLatestParameterPushHistory(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $packageName = null,
        ?string $serialNo = null,
        ?string $pushStatus = null,
        DateTimeInterface|string|null $pushTime = null,
    ): ParameterPushHistorySearchResult {
        return $this->searchParameterPushHistoryInternal(
            latest: true,
            optimized: false,
            pageNo: $pageNo,
            pageSize: $pageSize,
            packageName: $packageName,
            serialNo: $serialNo,
            pushStatus: $pushStatus,
            pushTime: $pushTime,
        );
    }

    public function searchOptimizedParameterPushHistory(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $packageName = null,
        ?string $serialNo = null,
        ?string $pushStatus = null,
        DateTimeInterface|string|null $pushTime = null,
    ): OptimizedParameterPushHistorySearchResult {
        return $this->searchParameterPushHistoryInternal(
            latest: false,
            optimized: true,
            pageNo: $pageNo,
            pageSize: $pageSize,
            packageName: $packageName,
            serialNo: $serialNo,
            pushStatus: $pushStatus,
            pushTime: $pushTime,
        );
    }

    public function searchLatestOptimizedParameterPushHistory(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $packageName = null,
        ?string $serialNo = null,
        ?string $pushStatus = null,
        DateTimeInterface|string|null $pushTime = null,
    ): OptimizedParameterPushHistorySearchResult {
        return $this->searchParameterPushHistoryInternal(
            latest: true,
            optimized: true,
            pageNo: $pageNo,
            pageSize: $pageSize,
            packageName: $packageName,
            serialNo: $serialNo,
            pushStatus: $pushStatus,
            pushTime: $pushTime,
        );
    }

    private function searchParameterPushHistoryInternal(
        bool $latest,
        bool $optimized,
        int $pageNo,
        int $pageSize,
        ?string $packageName,
        ?string $serialNo,
        ?string $pushStatus,
        DateTimeInterface|string|null $pushTime,
    ): ParameterPushHistorySearchResult|OptimizedParameterPushHistorySearchResult {
        $this->assertPage($pageNo, $pageSize);

        if (trim((string) $packageName) === '') {
            throw new PMarketAPIException('packageName cannot be null!');
        }

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
            'packageName' => $packageName,
        ];

        if ($serialNo !== null && $serialNo !== '') {
            $query['serialNo'] = $serialNo;
        }

        if ($pushStatus !== null && $pushStatus !== '') {
            $query['pushStatus'] = $this->normalizePushStatus($pushStatus);
        }

        if ($pushTime !== null && $pushTime !== '') {
            if ($pushTime instanceof DateTimeInterface) {
                $query['pushTime'] = (string) ($pushTime->getTimestamp() * 1000);
            } elseif (is_numeric($pushTime)) {
                $query['pushTime'] = (string) $pushTime;
            } else {
                $timestamp = strtotime((string) $pushTime);

                if ($timestamp === false) {
                    throw new PMarketAPIException('pushTime must be a valid date/time or millisecond timestamp.');
                }

                $query['pushTime'] = (string) ($timestamp * 1000);
            }
        }

        $query['onlyLastPushHistory'] = $latest ? 'true' : 'false';
        $query['optimizeParameters'] = $optimized ? 'true' : 'false';

        $endpoint = '/v1/3rdsys/parameter/push/history';

        $response = $this->request(
            method: 'GET',
            endpoint: $endpoint,
            query: $query,
            options: [
                'headers' => $this->defaultHeaders(),
            ],
            actionDescription: 'search parameter push history',
        );

        return $optimized
            ? $this->deserializeOptimizedParameterPushHistorySearchResult(
                $response,
                'search optimized parameter push history',
            )
            : $this->deserializeParameterPushHistorySearchResult(
                $response,
                'search parameter push history',
            );
    }

    private function normalizePushStatus(string $pushStatus): string
    {
        return match ($pushStatus) {
            'Success', 'success', '2' => '2',
            'Failed', 'failed', '3' => '3',
            default => throw new PMarketAPIException(
                'pushStatus must be one of Success, Failed, 2 or 3.'
            ),
        };
    }

    private function deserializeParameterPushHistorySearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): ParameterPushHistorySearchResult {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new PMarketAPIException(
                $this->errorMessageFromResponseBody($body, $actionDescription, $statusCode),
                $statusCode,
            );
        }

        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            throw new PMarketAPIException(
                sprintf('Could not decode P Market response for %s.', $actionDescription)
            );
        }

        if (($decoded['businessCode'] ?? null) !== 0) {
            throw new PMarketAPIException(
                $this->resultErrorMessage($decoded, $actionDescription, $statusCode),
                (int) ($decoded['businessCode'] ?? 0),
            );
        }

        $dataset = $decoded['dataset'] ?? [];

        $rows = [];

        foreach ($dataset as $item) {
            $rows[] = $this->serializer->denormalize(
                $item,
                ParameterPushHistory::class
            );
        }

        return new ParameterPushHistorySearchResult(
            pageNo: (int) ($decoded['pageNo'] ?? 1),
            limit: (int) ($decoded['limit'] ?? count($rows)),
            totalCount: (int) ($decoded['totalCount'] ?? count($rows)),
            hasNext: (bool) ($decoded['hasNext'] ?? false),
            dataSet: $rows,
        );
    }

    private function deserializeOptimizedParameterPushHistorySearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): OptimizedParameterPushHistorySearchResult {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new PMarketAPIException(
                $this->errorMessageFromResponseBody($body, $actionDescription, $statusCode),
                $statusCode,
            );
        }

        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            throw new PMarketAPIException(
                sprintf('Could not decode P Market response for %s.', $actionDescription)
            );
        }

        if (($decoded['businessCode'] ?? null) !== 0) {
            throw new PMarketAPIException(
                $this->resultErrorMessage($decoded, $actionDescription, $statusCode),
                (int) ($decoded['businessCode'] ?? 0),
            );
        }

        $dataset = $decoded['dataset'] ?? [];

        $rows = [];

        foreach ($dataset as $item) {
            $rows[] = $this->serializer->denormalize(
                $item,
                OptimizedParameterPushHistory::class
            );
        }

        return new OptimizedParameterPushHistorySearchResult(
            pageNo: (int) ($decoded['pageNo'] ?? 1),
            limit: (int) ($decoded['limit'] ?? count($rows)),
            totalCount: (int) ($decoded['totalCount'] ?? count($rows)),
            hasNext: (bool) ($decoded['hasNext'] ?? false),
            dataSet: $rows,
        );
    }
}