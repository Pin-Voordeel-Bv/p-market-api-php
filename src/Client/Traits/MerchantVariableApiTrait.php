<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\MerchantVariableCreateRequest;
use PinVandaag\PMarketAPI\Model\MerchantVariableUpdateRequest;
use PinVandaag\PMarketAPI\Model\ParameterVariableDTO;
use PinVandaag\PMarketAPI\Model\ParameterVariableDeleteRequest;
use PinVandaag\PMarketAPI\Model\ParameterVariableSearchResult;
use Psr\Http\Message\ResponseInterface;

trait MerchantVariableApiTrait
{
    public function searchMerchantVariable(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        int|string|null $merchantId = null,
        ?string $packageName = null,
        ?string $key = null,
        ?string $source = null,
    ): ParameterVariableSearchResult {
        $this->assertPage($pageNo, $pageSize);

        if ($merchantId === null || (string) $merchantId === '') {
            throw new PMarketAPIException('Parameter merchantId cannot be null at same time!');
        }

        $merchantId = $this->assertPositiveInteger($merchantId, 'merchantId');

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
            'merchantId' => (string) $merchantId,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeParameterVariableOrderBy($orderBy);
        }

        if ($packageName !== null && $packageName !== '') {
            $query['packageName'] = $packageName;
        }

        if ($key !== null && $key !== '') {
            $query['key'] = $key;
        }

        if ($source !== null && $source !== '') {
            $query['source'] = $this->normalizeParameterVariableSource($source);
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/merchant/variables',
            query: $query,
            options: [
                'headers' => $this->defaultHeaders(),
            ],
            actionDescription: 'search P Market merchant variables',
        );

        return $this->deserializeParameterVariableSearchResult(
            $response,
            'search P Market merchant variables',
        );
    }

    public function createMerchantVariable(MerchantVariableCreateRequest $request): bool
    {
        $this->assertMerchantVariableCreateRequest($request);

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/merchant/variables',
            actionDescription: 'create P Market merchant variable',
            headers: ['Content-Type' => 'application/json'],
            body: $this->merchantVariableCreatePayload($request),
        );

        return true;
    }

    public function updateMerchantVariable(
        int|string $merchantVariableId,
        MerchantVariableUpdateRequest $request,
    ): bool {
        $merchantVariableId = $this->assertPositiveInteger($merchantVariableId, 'merchantVariableId');
        $this->assertMerchantVariableUpdateRequest($request);

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/merchant/variables/%s', rawurlencode((string) $merchantVariableId)),
            actionDescription: sprintf('update P Market merchant variable "%s"', $merchantVariableId),
            headers: ['Content-Type' => 'application/json'],
            body: $this->merchantVariableUpdatePayload($request),
        );

        return true;
    }

    public function deleteMerchantVariable(int|string $merchantVariableId): bool
    {
        $merchantVariableId = $this->assertPositiveInteger($merchantVariableId, 'merchantVariableId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rdsys/merchant/variables/%s', rawurlencode((string) $merchantVariableId)),
            actionDescription: sprintf('delete P Market merchant variable "%s"', $merchantVariableId),
        );

        return true;
    }

    public function batchDeletionMerchantVariable(ParameterVariableDeleteRequest $request): bool
    {
        if ($request->variableIds === []) {
            throw new PMarketAPIException('variableIds cannot be empty!');
        }

        $this->emptyResult(
            method: 'DELETE',
            endpoint: '/v1/3rdsys/merchant/variables/batch/deletion',
            actionDescription: 'batch delete P Market merchant variables',
            headers: ['Content-Type' => 'application/json'],
            body: [
                'variableIds' => array_values($request->variableIds),
            ],
        );

        return true;
    }

    private function deserializeParameterVariableSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): ParameterVariableSearchResult {
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

        $variables = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $variableData) {
            if (is_array($variableData)) {
                $variables[] = $this->serializer->denormalize($variableData, ParameterVariableDTO::class);
            }
        }

        return new ParameterVariableSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($variables)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($variables),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $variables,
        );
    }

    private function assertMerchantVariableCreateRequest(MerchantVariableCreateRequest $request): void
    {
        $errors = [];

        try {
            $this->assertPositiveInteger($request->merchantId, 'merchantId');
        } catch (PMarketAPIException $exception) {
            $errors[] = 'Parameter merchantId cannot be null and cannot be less than 1!';
        }

        if ($request->variableList === []) {
            $errors[] = 'variableList can not be empty';
        }

        foreach ($request->variableList as $variable) {
            $errors = array_merge($errors, $this->validateParameterVariable($variable));
        }

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function assertMerchantVariableUpdateRequest(MerchantVariableUpdateRequest $request): void
    {
        if (
            $request->packageName === null
            && $request->type === null
            && $request->key === null
            && $request->value === null
            && $request->remarks === null
        ) {
            throw new PMarketAPIException('updateRequest cannot be empty.');
        }

        $errors = $this->validateParameterVariable($request, allowEmptyKey: true);

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function validateParameterVariable(object|array $variable, bool $allowEmptyKey = false): array
    {
        $errors = [];

        $type = $variable instanceof MerchantVariableUpdateRequest || is_object($variable)
            ? ($variable->type ?? null)
            : ($variable['type'] ?? null);

        $key = $variable instanceof MerchantVariableUpdateRequest || is_object($variable)
            ? ($variable->key ?? null)
            : ($variable['key'] ?? null);

        $value = $variable instanceof MerchantVariableUpdateRequest || is_object($variable)
            ? ($variable->value ?? null)
            : ($variable['value'] ?? null);

        $remarks = $variable instanceof MerchantVariableUpdateRequest || is_object($variable)
            ? ($variable->remarks ?? null)
            : ($variable['remarks'] ?? null);

        if (!$allowEmptyKey && trim((string) $key) === '') {
            $errors[] = 'Variable key is mandatory';
        }

        if ($key !== null && trim((string) $key) !== '') {
            if (!preg_match('/^[A-Za-z0-9_.-]+$/', (string) $key)) {
                $errors[] = 'Variable key is invalid, only letters, numbers, dash, underline and dot is allowed';
            }

            if (mb_strlen((string) $key) > 128) {
                $errors[] = 'Variable key is too long';
            }
        }

        if ($type !== null && trim((string) $type) !== '') {
            $this->normalizeParameterVariableType((string) $type);
        }

        if ($value !== null && mb_strlen((string) $value) > 5000) {
            $errors[] = 'Variable value is too long';
        }

        if ($remarks !== null && mb_strlen((string) $remarks) > 500) {
            $errors[] = 'Variable remarks is too long';
        }

        return $errors;
    }

    private function merchantVariableCreatePayload(MerchantVariableCreateRequest $request): array
    {
        return [
            'merchantId' => $this->assertPositiveInteger($request->merchantId, 'merchantId'),
            'variableList' => array_map(
                fn ($variable): array => $this->parameterVariablePayload($variable),
                $request->variableList,
            ),
        ];
    }

    private function merchantVariableUpdatePayload(MerchantVariableUpdateRequest $request): array
    {
        return $this->parameterVariablePayload($request);
    }

    private function parameterVariablePayload(object|array $variable): array
    {
        $packageName = is_object($variable) ? ($variable->packageName ?? null) : ($variable['packageName'] ?? null);
        $type = is_object($variable) ? ($variable->type ?? null) : ($variable['type'] ?? null);
        $key = is_object($variable) ? ($variable->key ?? null) : ($variable['key'] ?? null);
        $value = is_object($variable) ? ($variable->value ?? null) : ($variable['value'] ?? null);
        $remarks = is_object($variable) ? ($variable->remarks ?? null) : ($variable['remarks'] ?? null);

        return array_filter([
            'packageName' => $packageName,
            'type' => $type !== null && $type !== '' ? $this->normalizeParameterVariableType((string) $type) : null,
            'key' => $key,
            'value' => $value,
            'remarks' => $remarks,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function normalizeParameterVariableOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'Variable_asc', 'variable_asc', 'createdDate ASC' => 'createdDate ASC',
            'Variable_desc', 'variable_desc', 'createdDate DESC' => 'createdDate DESC',
            default => throw new PMarketAPIException('orderBy must be one of Variable_asc, Variable_desc, createdDate ASC or createdDate DESC.'),
        };
    }

    private function normalizeParameterVariableSource(string $source): string
    {
        return match ($source) {
            'Market', 'market', 'M' => 'M',
            'Merchant', 'merchant', 'C' => 'C',
            default => throw new PMarketAPIException('source must be one of Market, Merchant, M or C.'),
        };
    }

    private function normalizeParameterVariableType(string $type): string
    {
        return match ($type) {
            'Text', 'text', 'T' => 'T',
            'Password', 'password', 'P' => 'P',
            default => throw new PMarketAPIException('type must be one of Text, Password, T or P.'),
        };
    }
}
