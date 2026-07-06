<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\MerchantCategory;
use PinVandaag\PMarketAPI\Model\MerchantCategoryRequest;
use Psr\Http\Message\ResponseInterface;

trait MerchantCategoryApiTrait
{
    public function getMerchantCategories(?string $name = null): array
    {
        $query = [];
        if ($name !== null && $name !== '') {
            $query['name'] = $name;
        }

        return $this->getResultDataArray(
            endpoint: '/v1/3rdsys/merchantCategories',
            responseClass: MerchantCategory::class,
            actionDescription: 'get P Market merchant categories',
            query: $query,
        );
    }

    public function createMerchantCategory(MerchantCategoryRequest $request): MerchantCategory
    {
        $this->assertMerchantCategoryRequest($request);

       return $this->postResultData(
            endpoint: '/v1/3rdsys/merchantCategories',
            responseClass: MerchantCategory::class,
            actionDescription: 'create P Market merchant category',
            body: $this->merchantCategoryPayload($request),
        );
    }

    public function updateMerchantCategory(int|string $merchantCategoryId, MerchantCategoryRequest $request): MerchantCategory
    {
        $merchantCategoryId = $this->assertPositiveInteger($merchantCategoryId, 'merchantCategoryId');
        $this->assertMerchantCategoryRequest($request);

        return $this->putResultData(
            endpoint: sprintf('/v1/3rdsys/merchantCategories/%s', rawurlencode((string) $merchantCategoryId)),
            responseClass: MerchantCategory::class,
            actionDescription: sprintf('update P Market merchant category "%s"', $merchantCategoryId),
            body: $this->merchantCategoryPayload($request),
        );
    }

    public function deleteMerchantCategory(int|string $merchantCategoryId): bool
    {
        $merchantCategoryId = $this->assertPositiveInteger($merchantCategoryId, 'merchantCategoryId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rdsys/merchantCategories/%s', rawurlencode((string) $merchantCategoryId)),
            actionDescription: sprintf('delete P Market merchant category "%s"', $merchantCategoryId),
        );

        return true;
    }

    public function batchCreateMerchantCategory(array $requests, bool $skipExist = false): array
    {
        if ($requests === []) {
            throw new PMarketAPIException('Parameter merchantCategoryBatchCreateRequest cannot be null and empty!');
        }

        foreach ($requests as $request) {
            if (!$request instanceof MerchantCategoryRequest) {
                throw new PMarketAPIException('All batch items must be MerchantCategoryRequest.');
            }
            $this->assertMerchantCategoryRequest($request);
        }

        return $this->postResultDataArray(
            endpoint: '/v1/3rdsys/merchantCategories/batch',
            responseClass: MerchantCategory::class,
            actionDescription: 'batch create P Market merchant categories',
            query: ['skipExist' => $this->boolString($skipExist)],
            body: array_map(fn (MerchantCategoryRequest $request): array => $this->merchantCategoryPayload($request), $requests),
        );
    }

    private function assertMerchantCategoryRequest(MerchantCategoryRequest $request): void
    {
        $validationErrors = [];

        if (trim($request->name) === '') {
            $validationErrors[] = 'name:may not be empty';
        }

        if (mb_strlen($request->name) > 128) {
            $validationErrors[] = 'name:length must be between 0 and 128';
        }

        if ($request->remarks !== null && mb_strlen($request->remarks) > 255) {
            $validationErrors[] = 'remarks:length must be between 0 and 255';
        }

        if ($validationErrors !== []) {
            throw new PMarketAPIException(implode('; ', $validationErrors));
        }
    }

    private function merchantCategoryPayload(MerchantCategoryRequest $request): array
    {
        return array_filter([
            'name' => $request->name,
            'remarks' => $request->remarks,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function getResultDataArray(
        string $endpoint,
        string $responseClass,
        string $actionDescription,
        array $query = [],
        array $headers = [],
    ): array {
        $response = $this->request(
            method: 'GET',
            endpoint: $endpoint,
            query: $query,
            options: ['headers' => $this->defaultHeaders() + $headers],
            actionDescription: $actionDescription,
        );

        return $this->deserializeResultDataArray($response, $responseClass, $actionDescription);
    }

    private function postResultDataArray(
        string $endpoint,
        string $responseClass,
        string $actionDescription,
        array $query = [],
        array $body = [],
        array $headers = [],
    ): array {
        $response = $this->request(
            method: 'POST',
            endpoint: $endpoint,
            query: $query,
            options: [
                'headers' => $this->defaultHeaders() + ['Content-Type' => 'application/json'] + $headers,
                'json' => $body,
            ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeResultDataArray($response, $responseClass, $actionDescription);
    }

    private function deserializeResultDataArray(
        ResponseInterface $response,
        string $responseClass,
        string $actionDescription,
    ): array {
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

        $data = $decoded['data'] ?? [];
        if (!is_array($data)) {
            return [];
        }

        return array_values(array_map(
            fn (array $item): object => $this->serializer->denormalize($item, $responseClass),
            array_filter($data, 'is_array')
        ));
    }
}
