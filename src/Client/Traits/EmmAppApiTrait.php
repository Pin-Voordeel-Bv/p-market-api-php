<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\EmmApp;
use PinVandaag\PMarketAPI\Model\EmmAppAvailableTestVersionList;
use PinVandaag\PMarketAPI\Model\EmmAppCreateRequest;
use PinVandaag\PMarketAPI\Model\EmmAppDetail;
use PinVandaag\PMarketAPI\Model\EmmAppPermission;
use PinVandaag\PMarketAPI\Model\EmmAppSearchResult;
use PinVandaag\PMarketAPI\Model\SubscribeEmmApp;
use PinVandaag\PMarketAPI\Model\SubscribeEmmAppSearchResult;
use Psr\Http\Message\ResponseInterface;

trait EmmAppApiTrait
{
    public function searchEmmApp(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        string $resellerName = '',
        ?string $keyWords = null,
        ?string $type = null,
    ): EmmAppSearchResult {
        $this->assertPage($pageNo, $pageSize);

        if (trim($resellerName) === '') {
            throw new PMarketAPIException('Parameter resellerName cannot be null!');
        }

        if (mb_strlen($resellerName) > 64) {
            throw new PMarketAPIException('Parameter resellerName is too long, maxlength is 64!');
        }

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
            'resellerName' => $resellerName,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeEmmAppOrderBy($orderBy);
        }

        if ($keyWords !== null && $keyWords !== '') {
            $query['keyWords'] = $keyWords;
        }

        if ($type !== null && $type !== '') {
            $query['appType'] = $this->normalizeEmmAppType($type);
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/emm/apps',
            query: $query,
            options: ['headers' => $this->defaultHeaders()],
            actionDescription: 'search P Market EMM apps',
        );

        return $this->deserializeEmmAppSearchResult($response, 'search P Market EMM apps');
    }

    public function createEmmApp(EmmAppCreateRequest $request): EmmApp
    {
        $this->assertEmmAppCreateRequest($request);

        return $this->postResultData(
            endpoint: '/v1/3rdsys/emm/apps',
            responseClass: EmmApp::class,
            actionDescription: 'create P Market EMM app',
            body: [
                'resellerName' => $request->resellerName,
                'packageName' => $request->packageName,
            ],
        );
    }

    public function getEmmAppDetail(int|string $appId): EmmAppDetail
    {
        $appId = $this->assertPositiveInteger($appId, 'appId');

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/emm/apps/%s', rawurlencode((string) $appId)),
            responseClass: EmmAppDetail::class,
            actionDescription: sprintf('get P Market EMM app detail "%s"', $appId),
        );
    }

    public function removeEmmApp(int|string $appId, string $resellerName): bool
    {
        $appId = $this->assertPositiveInteger($appId, 'appId');
        $this->assertEmmResellerName($resellerName);

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rdsys/emm/apps/%s', rawurlencode((string) $appId)),
            actionDescription: sprintf('remove P Market EMM app "%s"', $appId),
            query: ['resellerName' => $resellerName],
        );

        return true;
    }

    public function searchSubscribeEmmApp(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $name = null,
        ?bool $isSubscribed = null,
    ): SubscribeEmmAppSearchResult {
        $this->assertPage($pageNo, $pageSize);

        if ($isSubscribed === null) {
            throw new PMarketAPIException('Parameter isSubscribed cannot be null');
        }

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
            'isSubscribed' => $isSubscribed ? 'true' : 'false',
        ];

        if ($name !== null && $name !== '') {
            $query['name'] = $name;
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/emm/apps/subscription',
            query: $query,
            options: ['headers' => $this->defaultHeaders()],
            actionDescription: 'search P Market subscribed EMM apps',
        );

        return $this->deserializeSubscribeEmmAppSearchResult(
            $response,
            'search P Market subscribed EMM apps',
        );
    }

    public function subscribeEmmApp(int|string $appId): bool
    {
        $appId = $this->assertPositiveInteger($appId, 'appId');

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/emm/apps/%s/subscribe', rawurlencode((string) $appId)),
            actionDescription: sprintf('subscribe P Market EMM app "%s"', $appId),
            headers: ['Content-Type' => 'application/json'],
        );

        return true;
    }

    public function unSubscribeEmmApp(int|string $appId): bool
    {
        $appId = $this->assertPositiveInteger($appId, 'appId');

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/emm/apps/%s/unsubscribe', rawurlencode((string) $appId)),
            actionDescription: sprintf('unsubscribe P Market EMM app "%s"', $appId),
            headers: ['Content-Type' => 'application/json'],
        );

        return true;
    }

    public function getEmmAppPermissionList(int|string $appId): EmmAppPermission
    {
        $appId = $this->assertPositiveInteger($appId, 'appId');

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/emm/apps/%s/permissions', rawurlencode((string) $appId)),
            responseClass: EmmAppPermission::class,
            actionDescription: sprintf('get P Market EMM app permissions "%s"', $appId),
        );
    }

    public function getAvailableTestTrackVersionList(int|string $appId): EmmAppAvailableTestVersionList
    {
        $appId = $this->assertPositiveInteger($appId, 'appId');

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/emm/apps/%s/available/test/versions', rawurlencode((string) $appId)),
            responseClass: EmmAppAvailableTestVersionList::class,
            actionDescription: sprintf('get P Market EMM app available test versions "%s"', $appId),
        );
    }

    private function assertEmmAppCreateRequest(EmmAppCreateRequest $request): void
    {
        $this->assertEmmResellerName($request->resellerName);

        if (trim($request->packageName) === '') {
            throw new PMarketAPIException('Parameter packageName cannot be null!');
        }

        if (mb_strlen($request->packageName) > 128) {
            throw new PMarketAPIException('Parameter packageName is too long, maxlength is 128!');
        }
    }

    private function assertEmmResellerName(string $resellerName): void
    {
        if (trim($resellerName) === '') {
            throw new PMarketAPIException('Parameter resellerName cannot be null!');
        }

        if (mb_strlen($resellerName) > 64) {
            throw new PMarketAPIException('Parameter resellerName is too long, maxlength is 64!');
        }
    }

    private function deserializeEmmAppSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): EmmAppSearchResult {
        return $this->deserializeEmmPageResult($response, $actionDescription, EmmApp::class, EmmAppSearchResult::class);
    }

    private function deserializeSubscribeEmmAppSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): SubscribeEmmAppSearchResult {
        return $this->deserializeEmmPageResult($response, $actionDescription, SubscribeEmmApp::class, SubscribeEmmAppSearchResult::class);
    }

    private function deserializeEmmPageResult(
        ResponseInterface $response,
        string $actionDescription,
        string $rowClass,
        string $resultClass,
    ): EmmAppSearchResult|SubscribeEmmAppSearchResult {
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
        $dataSet = $pageInfo['dataSet'] ?? $pageInfo['dataset'] ?? $decoded['dataset'] ?? [];

        $rows = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $rowData) {
            if (is_array($rowData)) {
                $rows[] = $this->serializer->denormalize($rowData, $rowClass);
            }
        }

        return new $resultClass(
            pageNo: (int) ($pageInfo['pageNo'] ?? $decoded['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? $decoded['limit'] ?? count($rows)),
            totalCount: isset($pageInfo['totalCount'])
                ? (int) $pageInfo['totalCount']
                : (int) ($decoded['totalCount'] ?? count($rows)),
            hasNext: (bool) ($pageInfo['hasNext'] ?? $decoded['hasNext'] ?? false),
            dataSet: $rows,
        );
    }

    private function normalizeEmmAppOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'EmmAppName_desc', 'emmAppName_desc', 'a.name DESC' => 'a.name DESC',
            'EmmAppName_asc', 'emmAppName_asc', 'a.name ASC' => 'a.name ASC',
            'EmmUpdatedDate_desc', 'emmUpdatedDate_desc', 'a.updated_date DESC' => 'a.updated_date DESC',
            'EmmUpdatedDate_asc', 'emmUpdatedDate_asc', 'a.updated_date ASC' => 'a.updated_date ASC',
            default => throw new PMarketAPIException(
                'orderBy must be one of EmmAppName_desc, EmmAppName_asc, EmmUpdatedDate_desc or EmmUpdatedDate_asc.'
            ),
        };
    }

    private function normalizeEmmAppType(string $type): string
    {
        return match ($type) {
            'GOOGLE', 'Google', 'google', 'G' => 'G',
            'PRIVATE', 'Private', 'private', 'P' => 'P',
            default => throw new PMarketAPIException('type must be one of GOOGLE, PRIVATE, G or P.'),
        };
    }
}
