<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\CreateTerminalApkPartialParamRequest;
use PinVandaag\PMarketAPI\Model\CreateTerminalApkRequest;
use PinVandaag\PMarketAPI\Model\FileParameter;
use PinVandaag\PMarketAPI\Model\TerminalApk;
use PinVandaag\PMarketAPI\Model\TerminalApkSearchResult;
use PinVandaag\PMarketAPI\Model\UpdateTerminalApkRequest;
use Psr\Http\Message\ResponseInterface;

trait TerminalApkApiTrait
{
    public function createTerminalApk(CreateTerminalApkRequest $request): TerminalApk
    {
        $this->assertCreateTerminalApkRequest($request);

        return $this->postResultData(
            endpoint: '/v1/3rdsys/terminalApks',
            responseClass: TerminalApk::class,
            actionDescription: 'create P Market terminal APK push task',
            body: $this->createTerminalApkPayload($request),
        );
    }

    public function createTerminalApkWithPartialParams(CreateTerminalApkPartialParamRequest $request): TerminalApk
    {
        $this->assertCreateTerminalApkPartialParamRequest($request);

        return $this->postResultData(
            endpoint: '/v1/3rdsys/terminalApks/part/param',
            responseClass: TerminalApk::class,
            actionDescription: 'create P Market terminal APK partial parameter push task',
            body: $this->createTerminalApkPartialPayload($request),
        );
    }

    public function searchTerminalApk(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $terminalTid = null,
        ?string $appPackageName = null,
        ?string $status = null,
        ?string $serialNo = null,
        array $pidList = [],
    ): TerminalApkSearchResult {
        $this->assertPage($pageNo, $pageSize);

        if (trim((string) $terminalTid) === '' && trim((string) $serialNo) === '') {
            throw new PMarketAPIException('The property serialNo and tid in request cannot be blank at same time!');
        }

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeApkCreatedDateOrderBy($orderBy);
        }

        if ($terminalTid !== null && $terminalTid !== '') {
            $query['terminalTid'] = $terminalTid;
        }

        if ($appPackageName !== null && $appPackageName !== '') {
            $query['appPackageName'] = $appPackageName;
        }

        if ($status !== null && $status !== '') {
            $query['status'] = $this->normalizeApkPushStatus($status);
        }

        if ($serialNo !== null && $serialNo !== '') {
            $query['serialNo'] = $serialNo;
        }

        if ($pidList !== []) {
            $query['pidList'] = implode(',', array_values(array_filter(array_map('trim', $pidList))));
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/terminalApks',
            query: $query,
            options: ['headers' => $this->defaultHeaders()],
            actionDescription: 'search P Market terminal APK push tasks',
        );

        return $this->deserializeTerminalApkSearchResult($response, 'search P Market terminal APK push tasks');
    }

    public function getTerminalApk(int|string $terminalApkId, array $pidList = []): TerminalApk
    {
        $terminalApkId = $this->assertPositiveInteger($terminalApkId, 'terminalApkId');

        $query = [];
        if ($pidList !== []) {
            $query['pidList'] = implode(',', array_values(array_filter(array_map('trim', $pidList))));
        }

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/terminalApks/%s', rawurlencode((string) $terminalApkId)),
            responseClass: TerminalApk::class,
            actionDescription: sprintf('get P Market terminal APK "%s"', $terminalApkId),
            query: $query,
        );
    }

    public function disableApkPush(UpdateTerminalApkRequest $request): bool
    {
        $this->assertUpdateTerminalApkRequest($request, 'disableTerminalApkRequest');

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/terminalApks/suspend',
            actionDescription: 'disable P Market terminal APK push task',
            headers: ['Content-Type' => 'application/json'],
            body: $this->updateTerminalApkPayload($request),
        );

        return true;
    }

    public function uninstallApk(UpdateTerminalApkRequest $request): bool
    {
        $this->assertUpdateTerminalApkRequest($request, 'uninstallTerminalApkRequest');

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/terminalApks/uninstall',
            actionDescription: 'uninstall P Market terminal APK',
            headers: ['Content-Type' => 'application/json'],
            body: $this->updateTerminalApkPayload($request),
        );

        return true;
    }

    public function deleteTerminalApk(int|string $terminalApkId): bool
    {
        $terminalApkId = $this->assertPositiveInteger($terminalApkId, 'terminalApkId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rdsys/terminalApks/%s', rawurlencode((string) $terminalApkId)),
            actionDescription: sprintf('delete P Market terminal APK "%s"', $terminalApkId),
        );

        return true;
    }

    private function assertCreateTerminalApkRequest(CreateTerminalApkRequest $request): void
    {
        $errors = $this->validateCommonApkPushRequest(
            tid: $request->tid,
            serialNo: $request->serialNo,
            packageName: $request->packageName,
            parameters: $request->parameters,
            base64FileParameters: $request->base64FileParameters,
            context: 'createTerminalApkRequest',
        );

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function assertCreateTerminalApkPartialParamRequest(CreateTerminalApkPartialParamRequest $request): void
    {
        $errors = $this->validateCommonApkPushRequest(
            tid: $request->tid,
            serialNo: $request->serialNo,
            packageName: $request->packageName,
            parameters: $request->parameters,
            base64FileParameters: $request->base64FileParameters,
            context: 'createTerminalApkRequest',
        );

        if (trim($request->partialPid) === '') {
            $errors[] = 'partialPid:may not be empty';
        }

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function assertUpdateTerminalApkRequest(UpdateTerminalApkRequest $request, string $context): void
    {
        $errors = [];

        if (trim((string) $request->tid) === '' && trim((string) $request->serialNo) === '') {
            $errors[] = sprintf('The property serialNo and tid in %s cannot be blank at same time!', $context);
        }

        if (trim($request->packageName) === '') {
            $errors[] = 'packageName:may not be empty';
        }

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    /**
     * Shared with TerminalGroupApkApiTrait later.
     */
    private function validateCommonApkPushRequest(
        ?string $tid,
        ?string $serialNo,
        string $packageName,
        array $parameters,
        array $base64FileParameters,
        string $context,
    ): array {
        $errors = [];

        if (trim((string) $tid) === '' && trim((string) $serialNo) === '') {
            $errors[] = sprintf('The property serialNo and tid in %s cannot be blank at same time!', $context);
        }

        if (trim($packageName) === '') {
            $errors[] = 'packageName:may not be empty';
        }

        if ($parameters === []) {
            $errors[] = sprintf('The property parameters of %s cannot be empty!', $context);
        }

        if (count($base64FileParameters) > 10) {
            $errors[] = 'Exceed max counter (10) of file type parameters!';
        }

        foreach ($base64FileParameters as $fileParameter) {
            $fileData = $fileParameter instanceof FileParameter
                ? (string) $fileParameter->fileData
                : (string) ($fileParameter['fileData'] ?? '');

            if ($fileData !== '' && strlen($fileData) > 512000) {
                $errors[] = 'Exceed max size (500kb) per file type parameters!';
                break;
            }
        }

        return $errors;
    }

    private function createTerminalApkPayload(CreateTerminalApkRequest $request): array
    {
        return $this->filterApkPayload([
            'tid' => $request->tid,
            'serialNo' => $request->serialNo,
            'packageName' => $request->packageName,
            'version' => $request->version,
            'templateName' => $request->templateName,
            'parameters' => $request->parameters,
            'base64FileParameters' => $this->fileParametersPayload($request->base64FileParameters),
            'pushTemplateName' => $request->pushTemplateName,
            'inheritPushHistory' => $request->inheritPushHistory,
            'forceUpdate' => $request->forceUpdate,
            'wifiOnly' => $request->wifiOnly,
            'effectiveTime' => $request->effectiveTime,
            'expiredTime' => $request->expiredTime,
            'validateUndefinedParameter' => $request->validateUndefinedParameter,
            'launcher' => $request->launcher,
        ]);
    }

    private function createTerminalApkPartialPayload(CreateTerminalApkPartialParamRequest $request): array
    {
        return $this->filterApkPayload([
            'tid' => $request->tid,
            'serialNo' => $request->serialNo,
            'packageName' => $request->packageName,
            'version' => $request->version,
            'templateName' => $request->templateName,
            'parameters' => $request->parameters,
            'base64FileParameters' => $this->fileParametersPayload($request->base64FileParameters),
            'pushTemplateName' => $request->pushTemplateName,
            'inheritPushHistory' => $request->inheritPushHistory,
            'forceUpdate' => $request->forceUpdate,
            'wifiOnly' => $request->wifiOnly,
            'effectiveTime' => $request->effectiveTime,
            'expiredTime' => $request->expiredTime,
            'partialPid' => $request->partialPid,
            'validateUndefinedParameter' => $request->validateUndefinedParameter,
        ]);
    }

    private function updateTerminalApkPayload(UpdateTerminalApkRequest $request): array
    {
        return $this->filterApkPayload([
            'tid' => $request->tid,
            'serialNo' => $request->serialNo,
            'packageName' => $request->packageName,
        ]);
    }

    private function fileParametersPayload(array $fileParameters): array
    {
        $payload = [];

        foreach ($fileParameters as $fileParameter) {
            if ($fileParameter instanceof FileParameter) {
                $payload[] = [
                    'pid' => $fileParameter->pid,
                    'fileName' => $fileParameter->fileName,
                    'fileData' => $fileParameter->fileData,
                ];
                continue;
            }

            if (is_array($fileParameter)) {
                $payload[] = array_filter([
                    'pid' => $fileParameter['pid'] ?? null,
                    'fileName' => $fileParameter['fileName'] ?? null,
                    'fileData' => $fileParameter['fileData'] ?? null,
                ], static fn ($value): bool => $value !== null && $value !== '');
            }
        }

        return $payload;
    }

    private function filterApkPayload(array $payload): array
    {
        return array_filter($payload, static fn ($value): bool => $value !== null && $value !== [] && $value !== '');
    }

    private function deserializeTerminalApkSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): TerminalApkSearchResult {
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

        $apks = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $apkData) {
            if (is_array($apkData)) {
                $apks[] = $this->serializer->denormalize($apkData, TerminalApk::class);
            }
        }

        return new TerminalApkSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($apks)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($apks),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $apks,
        );
    }

    /**
     * Shared with TerminalGroupApkApiTrait later.
     */
    private function normalizeApkCreatedDateOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'CreatedDate_desc', 'createdDate_desc', 'createdDate DESC' => 'createdDate DESC',
            'CreatedDate_asc', 'createdDate_asc', 'createdDate ASC' => 'createdDate ASC',
            default => throw new PMarketAPIException('orderBy must be one of CreatedDate_desc, CreatedDate_asc, createdDate DESC or createdDate ASC.'),
        };
    }

    /**
     * Shared with TerminalGroupApkApiTrait later.
     */
    private function normalizeApkPushStatus(string $status): string
    {
        return match ($status) {
            'Active' => 'A',
            'Suspend' => 'S',
            'Completed' => 'C',
            'A', 'S', 'C' => $status,
            default => throw new PMarketAPIException('status must be one of Active, Suspend, Completed, A, S or C.'),
        };
    }
}
