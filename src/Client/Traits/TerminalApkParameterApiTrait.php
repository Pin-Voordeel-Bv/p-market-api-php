<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\ApkParameter;
use PinVandaag\PMarketAPI\Model\ApkParameterSearchResult;
use PinVandaag\PMarketAPI\Model\CreateApkParameterRequest;
use PinVandaag\PMarketAPI\Model\FileParameter;
use PinVandaag\PMarketAPI\Model\UpdateApkParameterRequest;
use Psr\Http\Message\ResponseInterface;

trait TerminalApkParameterApiTrait
{
    public function searchTerminalApkParameter(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $templateName = null,
        string $packageName = '',
        string $versionName = '',
    ): ApkParameterSearchResult {
        $this->assertPage($pageNo, $pageSize);

        if (trim($packageName) === '') {
            throw new PMarketAPIException('Parameter packageName is mandatory!');
        }

        if (trim($versionName) === '') {
            throw new PMarketAPIException('Parameter versionName is mandatory!');
        }

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
            'packageName' => $packageName,
            'versionName' => $versionName,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeApkParameterOrderBy($orderBy);
        }

        if ($templateName !== null && $templateName !== '') {
            $query['templateName'] = $templateName;
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/apkParameters',
            query: $query,
            options: [
                'headers' => $this->defaultHeaders(),
            ],
            actionDescription: 'search P Market terminal APK parameters',
        );

        return $this->deserializeApkParameterSearchResult(
            $response,
            'search P Market terminal APK parameters',
        );
    }

    public function getTerminalApkParameter(
        int|string $apkParameterId,
        array $pidList = [],
    ): ApkParameter {
        $apkParameterId = $this->assertPositiveInteger($apkParameterId, 'apkParameterId');

        $query = [];

        if ($pidList !== []) {
            $query['pidList'] = implode(',', array_values(array_filter(array_map('trim', $pidList))));
        }

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/apkParameters/%s', rawurlencode((string) $apkParameterId)),
            responseClass: ApkParameter::class,
            actionDescription: sprintf('get P Market terminal APK parameter "%s"', $apkParameterId),
            query: $query,
        );
    }

    public function createApkParameter(CreateApkParameterRequest $request): bool
    {
        $this->assertCreateApkParameterRequest($request);

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/apkParameters',
            actionDescription: 'create P Market APK parameter',
            headers: ['Content-Type' => 'application/json'],
            body: $this->createApkParameterPayload($request),
        );

        return true;
    }

    public function updateApkParameter(
        int|string $apkParameterId,
        UpdateApkParameterRequest $request,
    ): bool {
        $apkParameterId = $this->assertPositiveInteger($apkParameterId, 'apkParameterId');
        $this->assertUpdateApkParameterRequest($request);

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/apkParameters/%s', rawurlencode((string) $apkParameterId)),
            actionDescription: sprintf('update P Market APK parameter "%s"', $apkParameterId),
            headers: ['Content-Type' => 'application/json'],
            body: $this->updateApkParameterPayload($request),
        );

        return true;
    }

    public function deleteApkParameter(int|string $apkParameterId): bool
    {
        $apkParameterId = $this->assertPositiveInteger($apkParameterId, 'apkParameterId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rdsys/apkParameters/%s', rawurlencode((string) $apkParameterId)),
            actionDescription: sprintf('delete P Market APK parameter "%s"', $apkParameterId),
        );

        return true;
    }

    private function assertCreateApkParameterRequest(CreateApkParameterRequest $request): void
    {
        $errors = [];

        if (trim($request->paramTemplateName ?? '') === '') {
            $errors[] = 'paramTemplateName:may not be empty';
        }

        if (trim($request->version) === '') {
            $errors[] = 'version:may not be empty';
        }

        if (trim($request->packageName) === '') {
            $errors[] = 'packageName:may not be empty';
        }

        if (trim($request->name) === '') {
            $errors[] = 'name:may not be empty';
        }

        if ($request->parameters === [] && $request->base64FileParameters === []) {
            $errors[] = 'parameters and base64FileParameters cannot be null at same time!';
        }

        $errors = array_merge(
            $errors,
            $this->validateApkParameterFileParameters($request->base64FileParameters)
        );

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function assertUpdateApkParameterRequest(UpdateApkParameterRequest $request): void
    {
        $errors = [];

        if (
            trim((string) $request->paramTemplateName) === ''
            && $request->parameters === []
            && $request->base64FileParameters === []
        ) {
            $errors[] = 'UpdateApkParameterRequest cannot be empty.';
        }

        $errors = array_merge(
            $errors,
            $this->validateApkParameterFileParameters($request->base64FileParameters)
        );

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function createApkParameterPayload(CreateApkParameterRequest $request): array
    {
        return $this->filterApkPayload([
            'packageName' => $request->packageName,
            'version' => $request->version,
            'name' => $request->name,
            'paramTemplateName' => $request->paramTemplateName,
            'parameters' => $request->parameters,
            'base64FileParameters' => $this->fileParametersPayload($request->base64FileParameters),
            'validateUndefinedParameter' => $request->validateUndefinedParameter,
        ]);
    }

    private function updateApkParameterPayload(UpdateApkParameterRequest $request): array
    {
        return $this->filterApkPayload([
            'paramTemplateName' => $request->paramTemplateName,
            'parameters' => $request->parameters,
            'base64FileParameters' => $this->fileParametersPayload($request->base64FileParameters),
            'validateUndefinedParameter' => $request->validateUndefinedParameter,
        ]);
    }

    private function validateApkParameterFileParameters(array $base64FileParameters): array
    {
        $errors = [];

        if (count($base64FileParameters) > 10) {
            $errors[] = 'Exceed max counter (10) of file type parameters!';
        }

        foreach ($base64FileParameters as $fileParameter) {
            if ($fileParameter instanceof FileParameter) {
                $fileData = (string) $fileParameter->fileData;
            } elseif (is_array($fileParameter)) {
                $fileData = (string) ($fileParameter['fileData'] ?? '');
            } else {
                $errors[] = 'base64FileParameters must contain FileParameter objects or arrays.';
                continue;
            }

            if ($fileData !== '' && strlen($fileData) > 512000) {
                $errors[] = 'Exceed max size (500kb) per file type parameters!';
                break;
            }
        }

        return $errors;
    }

    private function deserializeApkParameterSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): ApkParameterSearchResult {
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

        $parameters = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $parameterData) {
            if (is_array($parameterData)) {
                $parameters[] = $this->serializer->denormalize($parameterData, ApkParameter::class);
            }
        }

        return new ApkParameterSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($parameters)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($parameters),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $parameters,
        );
    }

    private function normalizeApkParameterOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'ApkParameter_asc', 'apkParameter_asc', 'a.created_date ASC' => 'a.created_date ASC',
            'ApkParameter_desc', 'apkParameter_desc', 'a.created_date DESC' => 'a.created_date DESC',
            default => throw new PMarketAPIException('orderBy must be one of ApkParameter_asc, ApkParameter_desc, a.created_date ASC or a.created_date DESC.'),
        };
    }
}
