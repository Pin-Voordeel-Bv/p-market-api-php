<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\EntityAttribute;
use PinVandaag\PMarketAPI\Model\EntityAttributeCreateRequest;
use PinVandaag\PMarketAPI\Model\EntityAttributeLabelInfo;
use PinVandaag\PMarketAPI\Model\EntityAttributeLabelUpdateRequest;
use PinVandaag\PMarketAPI\Model\EntityAttributeSearchResult;
use PinVandaag\PMarketAPI\Model\EntityAttributeUpdateRequest;
use Psr\Http\Message\ResponseInterface;

trait EntityAttributeApiTrait
{
    public function getEntityAttribute(int|string $attributeId): EntityAttribute
    {
        $attributeId = $this->assertPositiveInteger($attributeId, 'attributeId');

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/attributes/%s', rawurlencode((string) $attributeId)),
            responseClass: EntityAttribute::class,
            actionDescription: sprintf('get P Market entity attribute "%s"', $attributeId),
        );
    }

    public function searchEntityAttributes(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $key = null,
        ?string $entityType = null,
    ): EntityAttributeSearchResult {
        $this->assertPage($pageNo, $pageSize);

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeEntityAttributeOrderBy($orderBy);
        }

        if ($key !== null && $key !== '') {
            $query['key'] = $key;
        }

        if ($entityType !== null && $entityType !== '') {
            $query['entityType'] = $this->normalizeEntityAttributeType($entityType);
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/attributes',
            query: $query,
            options: [
                'headers' => $this->defaultHeaders(),
            ],
            actionDescription: 'search P Market entity attributes',
        );

        return $this->deserializeEntityAttributeSearchResult(
            $response,
            'search P Market entity attributes',
        );
    }

    public function createEntityAttribute(EntityAttributeCreateRequest $request): EntityAttribute
    {
        $this->assertEntityAttributeCreateRequest($request);

        return $this->postResultData(
            endpoint: '/v1/3rdsys/attributes',
            responseClass: EntityAttribute::class,
            actionDescription: 'create P Market entity attribute',
            body: $this->entityAttributeCreatePayload($request),
        );
    }

    public function updateEntityAttribute(
        int|string $attributeId,
        EntityAttributeUpdateRequest $request,
    ): EntityAttribute {
        $attributeId = $this->assertPositiveInteger($attributeId, 'attributeId');
        $this->assertEntityAttributeUpdateRequest($request);

        return $this->putResultData(
            endpoint: sprintf('/v1/3rdsys/attributes/%s', rawurlencode((string) $attributeId)),
            responseClass: EntityAttribute::class,
            actionDescription: sprintf('update P Market entity attribute "%s"', $attributeId),
            body: $this->entityAttributeUpdatePayload($request),
        );
    }

    public function updateEntityAttributeLabel(
        int|string $attributeId,
        EntityAttributeLabelUpdateRequest $request,
    ): bool {
        $attributeId = $this->assertPositiveInteger($attributeId, 'attributeId');
        $payload = $this->entityAttributeLabelUpdatePayload($request);

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/attributes/%s/label', rawurlencode((string) $attributeId)),
            actionDescription: sprintf('update P Market entity attribute "%s" labels', $attributeId),
            headers: ['Content-Type' => 'application/json'],
            body: $payload,
        );

        return true;
    }

    public function deleteEntityAttribute(int|string $attributeId): bool
    {
        $attributeId = $this->assertPositiveInteger($attributeId, 'attributeId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rdsys/attributes/%s', rawurlencode((string) $attributeId)),
            actionDescription: sprintf('delete P Market entity attribute "%s"', $attributeId),
        );

        return true;
    }

    private function deserializeEntityAttributeSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): EntityAttributeSearchResult {
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

        $attributes = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $attributeData) {
            if (is_array($attributeData)) {
                $attributes[] = $this->serializer->denormalize($attributeData, EntityAttribute::class);
            }
        }

        return new EntityAttributeSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($attributes)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($attributes),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $attributes,
        );
    }

    private function assertEntityAttributeCreateRequest(EntityAttributeCreateRequest $request): void
    {
        $errors = [];

        if (trim($request->entityType) === '') {
            $errors[] = 'entityType:may not be empty';
        }

        if (trim($request->inputType) === '') {
            $errors[] = 'inputType:may not be empty';
        }

        if (trim($request->key) === '') {
            $errors[] = 'key:may not be empty';
        }

        if (trim($request->defaultLabel) === '') {
            $errors[] = 'defaultLabel:may not be empty';
        }

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function assertEntityAttributeUpdateRequest(EntityAttributeUpdateRequest $request): void
    {
        $errors = [];

        if (trim($request->inputType) === '') {
            $errors[] = 'inputType:may not be empty';
        }

        if (trim($request->defaultLabel) === '') {
            $errors[] = 'defaultLabel:may not be empty';
        }

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function entityAttributeCreatePayload(EntityAttributeCreateRequest $request): array
    {
        return array_filter([
            'entityType' => $this->normalizeEntityAttributeType($request->entityType),
            'inputType' => $this->normalizeEntityInputTypeForBody($request->inputType),
            'minLength' => $request->minLength,
            'maxLength' => $request->maxLength,
            'required' => $request->required,
            'selector' => $request->selector,
            'key' => $request->key,
            'defaultLabel' => $request->defaultLabel,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function entityAttributeUpdatePayload(EntityAttributeUpdateRequest $request): array
    {
        return array_filter([
            'inputType' => $this->normalizeEntityInputTypeForBody($request->inputType),
            'minLength' => $request->minLength,
            'maxLength' => $request->maxLength,
            'required' => $request->required,
            'selector' => $request->selector,
            'defaultLabel' => $request->defaultLabel,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function entityAttributeLabelUpdatePayload(EntityAttributeLabelUpdateRequest $request): array
    {
        if ($request->entityAttributeLabelList === []) {
            throw new PMarketAPIException('entityAttributeLabelList cannot be empty.');
        }

        $labels = [];

        foreach ($request->entityAttributeLabelList as $labelInfo) {
            if ($labelInfo instanceof EntityAttributeLabelInfo) {
                $locale = trim((string) $labelInfo->locale);
                $label = trim((string) $labelInfo->label);
            } elseif (is_array($labelInfo)) {
                $locale = trim((string) ($labelInfo['locale'] ?? ''));
                $label = trim((string) ($labelInfo['label'] ?? ''));
            } else {
                throw new PMarketAPIException('entityAttributeLabelList must contain EntityAttributeLabelInfo objects or arrays.');
            }

            if ($locale === '') {
                throw new PMarketAPIException('locale cannot be empty.');
            }

            if ($label === '') {
                throw new PMarketAPIException('label cannot be empty.');
            }

            $labels[] = [
                'locale' => $locale,
                'label' => $label,
            ];
        }

        return [
            'entityAttributeLabelList' => $labels,
        ];
    }

    private function normalizeEntityAttributeOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'EntityType_desc', 'entityType_desc', 'entityType DESC', 'a.entity_type DESC' => 'a.entity_type DESC',
            'EntityType_asc', 'entityType_asc', 'entityType ASC', 'a.entity_type ASC' => 'a.entity_type ASC',
            default => throw new PMarketAPIException('orderBy must be one of EntityType_desc, EntityType_asc, entityType DESC or entityType ASC.'),
        };
    }

    private function normalizeEntityAttributeType(string $entityType): string
    {
        return match ($entityType) {
            'Merchant', 'Reseller', 'App' => $entityType,
            'merchant' => 'Merchant',
            'reseller' => 'Reseller',
            'app' => 'App',
            default => throw new PMarketAPIException('entityType must be one of Merchant, Reseller or App.'),
        };
    }

    private function normalizeEntityInputTypeForBody(string $inputType): string
    {
        return match ($inputType) {
            'Text', 'TEXT', 'text' => 'Text',
            'Selector', 'SELECTOR', 'selector' => 'Selector',
            default => throw new PMarketAPIException('inputType must be one of Text, Selector, TEXT or SELECTOR.'),
        };
    }
}
