<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\CreateTerminalGroupApkPartialParamRequest;
use PinVandaag\PMarketAPI\Model\CreateTerminalGroupApkRequest;
use PinVandaag\PMarketAPI\Model\TerminalGroupApk;
use PinVandaag\PMarketAPI\Model\TerminalGroupApkSearchResult;
use Psr\Http\Message\ResponseInterface;

trait TerminalGroupApkApiTrait
{
    public function getTerminalGroupApk(int|string $groupApkId, array $pidList = []): TerminalGroupApk
    {
        $groupApkId = $this->assertPositiveInteger($groupApkId, 'groupApkId');

        $query = [];
        if ($pidList !== []) {
            $query['pidList'] = implode(',', array_values(array_filter(array_map('trim', $pidList))));
        }

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/terminalGroupApks/%s', rawurlencode((string) $groupApkId)),
            responseClass: TerminalGroupApk::class,
            actionDescription: sprintf('get P Market terminal group APK "%s"', $groupApkId),
            query: $query,
        );
    }

    public function searchTerminalGroupApk(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        int|string|null $groupId = null,
        ?bool $pendingOnly = null,
        ?bool $historyOnly = null,
        ?string $keyWords = null,
    ): TerminalGroupApkSearchResult {
        $this->assertPage($pageNo, $pageSize);

        if ($groupId === null || (string) $groupId === '') {
            throw new PMarketAPIException('terminalGroupId cannot be null and cannot be less than 1!');
        }

        $groupId = $this->assertPositiveInteger($groupId, 'groupId');

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
            'groupId' => (string) $groupId,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeApkCreatedDateOrderBy($orderBy);
        }

        if ($pendingOnly !== null) {
            $query['pendingOnly'] = $this->boolString($pendingOnly);
        }

        if ($historyOnly !== null) {
            $query['historyOnly'] = $this->boolString($historyOnly);
        }

        if ($keyWords !== null && $keyWords !== '') {
            $query['keyWords'] = $keyWords;
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/terminalGroupApks',
            query: $query,
            options: ['headers' => $this->defaultHeaders()],
            actionDescription: 'search P Market terminal group APK tasks',
        );

        return $this->deserializeTerminalGroupApkSearchResult(
            $response,
            'search P Market terminal group APK tasks',
        );
    }

    public function createAndActiveGroupApk(CreateTerminalGroupApkRequest $request): TerminalGroupApk
    {
        $this->assertCreateTerminalGroupApkRequest($request);

        return $this->postResultData(
            endpoint: '/v1/3rdsys/terminalGroupApks',
            responseClass: TerminalGroupApk::class,
            actionDescription: 'create and activate P Market terminal group APK task',
            body: $this->createTerminalGroupApkPayload($request),
        );
    }

    public function createAndActiveGroupApkWithPartialParams(
        CreateTerminalGroupApkPartialParamRequest $request,
    ): TerminalGroupApk {
        $this->assertCreateTerminalGroupApkPartialParamRequest($request);

        return $this->postResultData(
            endpoint: '/v1/3rdsys/terminalGroupApks/part/param',
            responseClass: TerminalGroupApk::class,
            actionDescription: 'create and activate P Market terminal group APK partial parameter task',
            body: $this->createTerminalGroupApkPartialPayload($request),
        );
    }

    public function suspendTerminalGroupApk(int|string $groupApkId): TerminalGroupApk
    {
        $groupApkId = $this->assertPositiveInteger($groupApkId, 'groupApkId');

        return $this->postResultData(
            endpoint: sprintf('/v1/3rdsys/terminalGroupApks/%s/suspend', rawurlencode((string) $groupApkId)),
            responseClass: TerminalGroupApk::class,
            actionDescription: sprintf('suspend P Market terminal group APK "%s"', $groupApkId),
        );
    }

    public function deleteTerminalGroupApk(int|string $groupApkId): bool
    {
        $groupApkId = $this->assertPositiveInteger($groupApkId, 'groupApkId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rdsys/terminalGroupApks/%s', rawurlencode((string) $groupApkId)),
            actionDescription: sprintf('delete P Market terminal group APK "%s"', $groupApkId),
        );

        return true;
    }

    private function assertCreateTerminalGroupApkRequest(CreateTerminalGroupApkRequest $request): void
    {
        $errors = $this->validateCommonGroupApkPushRequest(
            groupId: $request->groupId,
            packageName: $request->packageName,
            base64FileParameters: $request->base64FileParameters,
        );

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function assertCreateTerminalGroupApkPartialParamRequest(
        CreateTerminalGroupApkPartialParamRequest $request,
    ): void {
        $errors = $this->validateCommonGroupApkPushRequest(
            groupId: $request->groupId,
            packageName: $request->packageName,
            base64FileParameters: $request->base64FileParameters,
        );

        if (trim($request->partialPid) === '') {
            $errors[] = 'partialPid:may not be empty';
        }

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function validateCommonGroupApkPushRequest(
        int|string $groupId,
        string $packageName,
        array $base64FileParameters,
    ): array {
        $errors = [];

        try {
            $this->assertPositiveInteger($groupId, 'groupId');
        } catch (PMarketAPIException $exception) {
            $errors[] = 'groupId cannot be null and cannot be less than 1!';
        }

        if (trim($packageName) === '') {
            $errors[] = 'packageName:may not be empty';
        }

        if (count($base64FileParameters) > 10) {
            $errors[] = 'Exceed max counter (10) of file type parameters!';
        }

        foreach ($base64FileParameters as $fileParameter) {
            $fileData = is_object($fileParameter)
                ? (string) ($fileParameter->fileData ?? '')
                : (string) ($fileParameter['fileData'] ?? '');

            if ($fileData !== '' && strlen($fileData) > 512000) {
                $errors[] = 'Exceed max size (500kb) per file type parameters!';
                break;
            }
        }

        return $errors;
    }

    private function createTerminalGroupApkPayload(CreateTerminalGroupApkRequest $request): array
    {
        return $this->filterApkPayload([
            'groupId' => $this->assertPositiveInteger($request->groupId, 'groupId'),
            'pushTemplateName' => $request->pushTemplateName,
            'packageName' => $request->packageName,
            'version' => $request->version,
            'templateName' => $request->templateName,
            'parameters' => $request->parameters,
            'base64FileParameters' => $this->fileParametersPayload($request->base64FileParameters),
            'inheritPushHistory' => $request->inheritPushHistory,
            'forceUpdate' => $request->forceUpdate,
            'wifiOnly' => $request->wifiOnly,
            'effectiveTime' => $request->effectiveTime,
            'expiredTime' => $request->expiredTime,
            'validateUndefinedParameter' => $request->validateUndefinedParameter,
            'launcher' => $request->launcher,
        ]);
    }

    private function createTerminalGroupApkPartialPayload(CreateTerminalGroupApkPartialParamRequest $request): array
    {
        return $this->filterApkPayload([
            'groupId' => $this->assertPositiveInteger($request->groupId, 'groupId'),
            'pushTemplateName' => $request->pushTemplateName,
            'packageName' => $request->packageName,
            'version' => $request->version,
            'templateName' => $request->templateName,
            'parameters' => $request->parameters,
            'base64FileParameters' => $this->fileParametersPayload($request->base64FileParameters),
            'inheritPushHistory' => $request->inheritPushHistory,
            'forceUpdate' => $request->forceUpdate,
            'wifiOnly' => $request->wifiOnly,
            'effectiveTime' => $request->effectiveTime,
            'expiredTime' => $request->expiredTime,
            'partialPid' => $request->partialPid,
            'validateUndefinedParameter' => $request->validateUndefinedParameter,
        ]);
    }

    private function deserializeTerminalGroupApkSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): TerminalGroupApkSearchResult {
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

        $tasks = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $taskData) {
            if (is_array($taskData)) {
                $tasks[] = $this->serializer->denormalize($taskData, TerminalGroupApk::class);
            }
        }

        return new TerminalGroupApkSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($tasks)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($tasks),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $tasks,
        );
    }
}
