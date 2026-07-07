<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Model\App;
use PinVandaag\PMarketAPI\Model\AppCost;
use PinVandaag\PMarketAPI\Model\AppSearchResult;
use PinVandaag\PMarketAPI\Model\ApkParamPid;
use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use Psr\Http\Message\ResponseInterface;

trait AppApiTrait
{
    public function searchApp(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $name = null,
        ?string $osType = null,
        ?string $chargeType = null,
        ?string $baseType = null,
        ?string $appStatus = null,
        ?string $apkStatus = null,
        ?bool $specificReseller = null,
        ?bool $specificMerchantCategory = null,
        ?bool $includeSubscribedApp = null,
        ?string $resellerName = null,
        ?string $modelName = null,
    ): AppSearchResult {
        $this->assertPage($pageNo, $pageSize);

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeAppOrderBy($orderBy);
        }

        if ($name !== null && $name !== '') {
            $query['name'] = $name;
        }

        if ($osType !== null && $osType !== '') {
            $query['osType'] = $this->normalizeAppOsType($osType);
        }

        if ($chargeType !== null && $chargeType !== '') {
            $query['chargeType'] = $this->normalizeAppChargeType($chargeType);
        }

        if ($baseType !== null && $baseType !== '') {
            $query['baseType'] = $this->normalizeAppBaseType($baseType);
        }

        if ($appStatus !== null && $appStatus !== '') {
            $query['appStatus'] = $this->normalizeAppStatus($appStatus);
        }

        if ($apkStatus !== null && $apkStatus !== '') {
            $query['apkStatus'] = $this->normalizeApkStatus($apkStatus);
        }

        if ($specificReseller !== null) {
            $query['specificReseller'] = $this->boolString($specificReseller);
        }

        if ($specificMerchantCategory !== null) {
            $query['specificMerchantCategory'] = $this->boolString($specificMerchantCategory);
        }

        if ($includeSubscribedApp !== null) {
            $query['includeSubscribedApp'] = $this->boolString($includeSubscribedApp);
        }

        if ($resellerName !== null && $resellerName !== '') {
            $query['resellerName'] = $resellerName;
        }

        if ($modelName !== null && $modelName !== '') {
            $query['modelName'] = $modelName;
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/apps',
            query: $query,
            options: [
                'headers' => $this->defaultHeaders(),
            ],
            actionDescription: 'search P Market apps',
        );

        return $this->deserializeAppSearchResult($response, 'search P Market apps');
    }

    public function getAppCost(int|string $resellerId, int|string $appId): AppCost
    {
        $resellerId = $this->assertPositiveInteger($resellerId, 'resellerId');
        $appId = $this->assertPositiveInteger($appId, 'appId');

        return $this->getResultData(
            endpoint: '/v1/3rdsys/apps/app-cost',
            responseClass: AppCost::class,
            actionDescription: sprintf('get P Market app cost for reseller "%s" and app "%s"', $resellerId, $appId),
            query: [
                'resellerId' => (string) $resellerId,
                'appId' => (string) $appId,
            ],
        );
    }

    public function searchApkParamPidList(
        string $paramTemplateName,
        string $packageName,
        string $versionName,
    ): ApkParamPid {
        $paramTemplateName = trim($paramTemplateName);
        $packageName = trim($packageName);
        $versionName = trim($versionName);

        if ($paramTemplateName === '') {
            throw new PMarketAPIException('Parameter templateName cannot be null!');
        }

        if ($packageName === '') {
            throw new PMarketAPIException('Parameter packageName cannot be null!');
        }

        if ($versionName === '') {
            throw new PMarketAPIException('Parameter versionName cannot be null!');
        }

        return $this->getResultData(
            endpoint: '/v1/3rdsys/apps/param/pid/list',
            responseClass: ApkParamPid::class,
            actionDescription: 'search P Market APK parameter PID list',
            query: [
                'paramTemplateName' => $paramTemplateName,
                'packageName' => $packageName,
                'versionName' => $versionName,
            ],
        );
    }

    private function deserializeAppSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): AppSearchResult {
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

        $apps = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $appData) {
            if (is_array($appData)) {
                $apps[] = $this->serializer->denormalize($appData, App::class);
            }
        }

        return new AppSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($apps)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($apps),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $apps,
        );
    }

    private function normalizeAppOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'AppName_desc', 'appName_desc', 'app.name DESC' => 'app.name DESC',
            'AppName_asc', 'appName_asc', 'app.name ASC' => 'app.name ASC',

            'UpdatedDate_desc', 'updatedDate_desc', 'app.updated_date DESC' => 'app.updated_date DESC',
            'UpdatedDate_asc', 'updatedDate_asc', 'app.updated_date ASC' => 'app.updated_date ASC',
            default => throw new PMarketAPIException(
                'orderBy must be one of AppName_desc, AppName_asc, Emial_desc, Emial_asc, UpdatedDate_desc or UpdatedDate_asc.'
            ),
        };
    }

    private function normalizeAppOsType(string $osType): string
    {
        return match ($osType) {
            'Android', 'android', 'A' => 'A',
            'Traditional', 'traditional', 'T' => 'T',
            default => throw new PMarketAPIException('osType must be one of Android, Traditional, A or T.'),
        };
    }

    private function normalizeAppChargeType(string $chargeType): string
    {
        return match ($chargeType) {
            'Free', 'free', '0', 0 => '0',
            'Charging', 'charging', '1', 1 => '1',
            default => throw new PMarketAPIException('chargeType must be one of Free, Charging, 0 or 1.'),
        };
    }

    private function normalizeAppBaseType(string $baseType): string
    {
        return match ($baseType) {
            'Normal', 'normal', 'N' => 'N',
            'Parameter', 'parameter', 'P' => 'P',
            default => throw new PMarketAPIException('baseType must be one of Normal, Parameter, N or P.'),
        };
    }

    private function normalizeAppStatus(string $appStatus): string
    {
        return match ($appStatus) {
            'Active', 'active', 'A' => 'A',
            'Suspend', 'suspend', 'S' => 'S',
            default => throw new PMarketAPIException('appStatus must be one of Active, Suspend, A or S.'),
        };
    }

    private function normalizeApkStatus(string $apkStatus): string
    {
        return match ($apkStatus) {
            'Pending', 'pending', 'P' => 'P',
            'Online', 'online', 'O' => 'O',
            'Rejected', 'rejected', 'R' => 'R',
            'Offline', 'offline', 'U' => 'U',
            default => throw new PMarketAPIException('apkStatus must be one of Pending, Online, Rejected, Offline, P, O, R or U.'),
        };
    }
}
