<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\TerminalGeoFenceWhiteList;
use PinVandaag\PMarketAPI\Model\TerminalGeoFenceWhiteListRequest;
use PinVandaag\PMarketAPI\Model\TerminalGeoFenceWhiteListSearchResult;
use Psr\Http\Message\ResponseInterface;

trait TerminalGeoFenceWhiteListApiTrait
{
    public function searchGeoFenceWhiteList(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $serialNo = null,
    ): TerminalGeoFenceWhiteListSearchResult {
        $this->assertPage($pageNo, $pageSize);

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeGeoFenceWhiteListOrderBy($orderBy);
        }

        if ($serialNo !== null && $serialNo !== '') {
            $query['serialNo'] = $serialNo;
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/terminal/geofence/whitelist',
            query: $query,
            options: ['headers' => $this->defaultHeaders()],
            actionDescription: 'search P Market terminal geofence whitelist',
        );

        return $this->deserializeTerminalGeoFenceWhiteListSearchResult(
            $response,
            'search P Market terminal geofence whitelist',
        );
    }

    public function createGeoFenceWhiteList(TerminalGeoFenceWhiteListRequest $request): bool
    {
        $this->assertTerminalGeoFenceWhiteListRequest($request, 'createRequest');

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/terminal/geofence/whitelist',
            actionDescription: 'create P Market terminal geofence whitelist',
            headers: ['Content-Type' => 'application/json'],
            body: ['serialNo' => $request->serialNo],
        );

        return true;
    }

    public function deleteGeoFenceWhiteList(TerminalGeoFenceWhiteListRequest $request): bool
    {
        $this->assertTerminalGeoFenceWhiteListRequest($request, 'deleteRequest');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: '/v1/3rdsys/terminal/geofence/whitelist',
            actionDescription: 'delete P Market terminal geofence whitelist',
            headers: ['Content-Type' => 'application/json'],
            body: ['serialNo' => $request->serialNo],
        );

        return true;
    }

    private function assertTerminalGeoFenceWhiteListRequest(
        TerminalGeoFenceWhiteListRequest $request,
        string $context,
    ): void {
        if (trim($request->serialNo) === '') {
            throw new PMarketAPIException(sprintf('Parameter %s cannot be null! Parameter serialNo cannot be null!', $context));
        }
    }

    private function deserializeTerminalGeoFenceWhiteListSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): TerminalGeoFenceWhiteListSearchResult {
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
            throw new PMarketAPIException(sprintf('Could not decode P Market response for %s.', $actionDescription));
        }

        if (($decoded['businessCode'] ?? null) !== 0) {
            throw new PMarketAPIException(
                $this->resultErrorMessage($decoded, $actionDescription, $statusCode),
                (int) ($decoded['businessCode'] ?? 0),
            );
        }

        $pageInfo = $decoded['pageInfo'] ?? $decoded;
        $dataSet = $pageInfo['dataSet'] ?? $pageInfo['dataset'] ?? [];

        $rows = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $rowData) {
            if (is_array($rowData)) {
                $rows[] = $this->serializer->denormalize($rowData, TerminalGeoFenceWhiteList::class);
            }
        }

        return new TerminalGeoFenceWhiteListSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($rows)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($rows),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $rows,
        );
    }

    private function normalizeGeoFenceWhiteListOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'CreatedDate_desc', 'createdDate_desc', 't.created_date DESC' => 't.created_date DESC',
            'CreatedDate_asc', 'createdDate_asc', 't.created_date ASC' => 't.created_date ASC',
            default => throw new PMarketAPIException(
                'orderBy must be one of CreatedDate_desc, CreatedDate_asc, t.created_date DESC or t.created_date ASC.'
            ),
        };
    }
}
