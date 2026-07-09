<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\EmmDeviceDashboardDetail;
use PinVandaag\PMarketAPI\Model\EmmDeviceDashboardDetailSearchResult;
use PinVandaag\PMarketAPI\Model\EmmDeviceDashboardMonitor;
use PinVandaag\PMarketAPI\Model\EmmDeviceInstalledApp;
use PinVandaag\PMarketAPI\Model\EmmDeviceInstalledAppSearchResult;
use PinVandaag\PMarketAPI\Model\EmmDeviceLocation;
use Psr\Http\Message\ResponseInterface;

trait EmmDeviceDetailApiTrait
{
    public function getEmmDeviceDashboardDetail(int|string $deviceId): EmmDeviceDashboardDetailSearchResult
    {
        $deviceId = $this->assertPositiveInteger($deviceId, 'deviceId');

        $response = $this->request(
            method: 'GET',
            endpoint: sprintf('/v1/3rdsys/emm/device/detail/%s', rawurlencode((string) $deviceId)),
            query: [],
            options: ['headers' => $this->defaultHeaders()],
            actionDescription: sprintf('get P Market EMM device dashboard detail "%s"', $deviceId),
        );

        return $this->deserializeEmmDeviceDashboardDetailSearchResult(
            $response,
            sprintf('get P Market EMM device dashboard detail "%s"', $deviceId),
        );
    }

    public function getEmmDeviceDashboardMonitor(int|string $deviceId): EmmDeviceDashboardMonitor
    {
        $deviceId = $this->assertPositiveInteger($deviceId, 'deviceId');

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/emm/device/detail/%s/monitor', rawurlencode((string) $deviceId)),
            responseClass: EmmDeviceDashboardMonitor::class,
            actionDescription: sprintf('get P Market EMM device dashboard monitor "%s"', $deviceId),
        );
    }

    public function searchDeviceInstalledApp(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        int|string|null $deviceId = null,
    ): EmmDeviceInstalledAppSearchResult {
        $this->assertPage($pageNo, $pageSize);

        if ($deviceId === null || (string) $deviceId === '') {
            throw new PMarketAPIException('Parameter deviceId cannot be null and cannot be less than 1!');
        }

        $deviceId = $this->assertPositiveInteger($deviceId, 'deviceId');

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeEmmDeviceInstalledAppOrderBy($orderBy);
        }

        $response = $this->request(
            method: 'GET',
            endpoint: sprintf('/v1/3rdsys/emm/device/detail/%s/installed-apps', rawurlencode((string) $deviceId)),
            query: $query,
            options: ['headers' => $this->defaultHeaders()],
            actionDescription: 'search P Market EMM device installed apps',
        );

        return $this->deserializeEmmDeviceInstalledAppSearchResult(
            $response,
            'search P Market EMM device installed apps',
        );
    }

    public function getEmmDeviceLocation(int|string $deviceId): EmmDeviceLocation
    {
        $deviceId = $this->assertPositiveInteger($deviceId, 'deviceId');

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/emm/device/detail/%s/location', rawurlencode((string) $deviceId)),
            responseClass: EmmDeviceLocation::class,
            actionDescription: sprintf('get P Market EMM device location "%s"', $deviceId),
        );
    }

    private function deserializeEmmDeviceDashboardDetailSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): EmmDeviceDashboardDetailSearchResult {
        return $this->deserializeEmmDeviceDetailPageResult(
            $response,
            $actionDescription,
            EmmDeviceDashboardDetail::class,
            EmmDeviceDashboardDetailSearchResult::class,
        );
    }

    private function deserializeEmmDeviceInstalledAppSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): EmmDeviceInstalledAppSearchResult {
        return $this->deserializeEmmDeviceDetailPageResult(
            $response,
            $actionDescription,
            EmmDeviceInstalledApp::class,
            EmmDeviceInstalledAppSearchResult::class,
        );
    }

    private function deserializeEmmDeviceDetailPageResult(
        ResponseInterface $response,
        string $actionDescription,
        string $rowClass,
        string $resultClass,
    ): EmmDeviceDashboardDetailSearchResult|EmmDeviceInstalledAppSearchResult {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new PMarketAPIException($this->errorMessageFromResponseBody($body, $actionDescription, $statusCode), $statusCode);
        }

        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            throw new PMarketAPIException(sprintf('Could not decode P Market response for %s.', $actionDescription));
        }

        if (($decoded['businessCode'] ?? null) !== 0) {
            throw new PMarketAPIException($this->resultErrorMessage($decoded, $actionDescription, $statusCode), (int) ($decoded['businessCode'] ?? 0));
        }

        $pageInfo = $decoded['pageInfo'] ?? $decoded;
        $dataSet = $pageInfo['dataSet'] ?? $pageInfo['dataset'] ?? [];

        $rows = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $rowData) {
            if (is_array($rowData)) {
                $rows[] = $this->serializer->denormalize($rowData, $rowClass);
            }
        }

        return new $resultClass(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($rows)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($rows),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $rows,
        );
    }

    private function normalizeEmmDeviceInstalledAppOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'AppName_desc', 'appName_desc', 'a.name DESC' => 'a.name DESC',
            'AppName_asc', 'appName_asc', 'a.name ASC' => 'a.name ASC',
            'AppSize_desc', 'appSize_desc', 'a.size DESC' => 'a.size DESC',
            'AppSize_asc', 'appSize_asc', 'a.size ASC' => 'a.size ASC',
            'AppInstallTime_desc', 'appInstallTime_desc', 'a.install_time DESC' => 'a.install_time DESC',
            'AppInstallTime_asc', 'appInstallTime_asc', 'a.install_time ASC' => 'a.install_time ASC',
            default => throw new PMarketAPIException(
                'orderBy must be one of AppName_desc, AppName_asc, AppSize_desc, AppSize_asc, AppInstallTime_desc or AppInstallTime_asc.'
            ),
        };
    }
}
