<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\Merchant;
use PinVandaag\PMarketAPI\Model\MerchantCreateRequest;
use PinVandaag\PMarketAPI\Model\MerchantSearchResult;
use PinVandaag\PMarketAPI\Model\MerchantUpdateRequest;
use Psr\Http\Message\ResponseInterface;

trait MerchantApiTrait
{
    public function searchMerchant(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $name = null,
        ?string $status = null,
        bool $includeEntityAttribute = false,
    ): MerchantSearchResult {
        $this->assertPage($pageNo, $pageSize);

        $query = [
            'limit' => (string) $pageSize,
            'pageNo' => (string) $pageNo,
            'includeEntityAttribute' => $this->boolString($includeEntityAttribute),
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeMerchantOrderBy($orderBy);
        }

        if ($name !== null && $name !== '') {
            $query['name'] = $name;
        }

        if ($status !== null && $status !== '') {
            $query['status'] = $this->normalizeMerchantStatus($status);
        }

        return $this->getMerchantResultPage(
            endpoint: '/v1/3rdsys/merchants',
            actionDescription: 'search P Market merchants',
            query: $query,
        );
    }

    /**
     * Retrieve a merchant by merchant ID.
     *
     * @throws PMarketAPIException
     */
    public function getMerchant(int|string $merchantId): Merchant
    {
        $merchantId = $this->assertPositiveInteger($merchantId, 'merchantId');

        /** @var Merchant $merchant */
        $merchant = $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/merchants/%s', rawurlencode((string) $merchantId)),
            responseClass: Merchant::class,
            actionDescription: sprintf('retrieve P Market merchant "%s"', $merchantId),
        );

        return $merchant;
    }

    public function createMerchant(MerchantCreateRequest $merchantCreateRequest): Merchant
    {
        $this->assertMerchantCreateRequest($merchantCreateRequest);

        /** @var Merchant $merchant */
        $merchant = $this->postResultData(
            endpoint: '/v1/3rdsys/merchants',
            responseClass: Merchant::class,
            actionDescription: 'create P Market merchant',
            body: $this->merchantCreatePayload($merchantCreateRequest),
        );

        return $merchant;
    }

    private function assertMerchantCreateRequest(MerchantCreateRequest $request): void
    {
        $validationErrors = [];

        if (trim($request->name) === '') {
            $validationErrors[] = 'name:may not be empty';
        }

        if (mb_strlen($request->name) > 128) {
            $validationErrors[] = 'name:length must be between 0 and 128';
        }

        if (trim($request->resellerName) === '') {
            $validationErrors[] = 'resellerName:may not be empty';
        }

        if (mb_strlen($request->resellerName) > 64) {
            $validationErrors[] = 'resellerName:length must be between 0 and 64';
        }

        if ($request->email !== null && $request->email !== '') {
            if (mb_strlen($request->email) > 255) {
                $validationErrors[] = 'email:length must be between 0 and 255';
            }

            if (filter_var($request->email, FILTER_VALIDATE_EMAIL) === false) {
                $validationErrors[] = 'email:not a well-formed email address';
            }
        }

        if ($request->contact !== null && mb_strlen($request->contact) > 64) {
            $validationErrors[] = 'contact:length must be between 0 and 64';
        }

        if ($request->country !== null && mb_strlen($request->country) > 64) {
            $validationErrors[] = 'country:length must be between 0 and 64';
        }

        if ($request->phone !== null && mb_strlen($request->phone) > 32) {
            $validationErrors[] = 'phone:length must be between 0 and 32';
        }

        if ($request->province !== null && mb_strlen($request->province) > 64) {
            $validationErrors[] = 'province:length must be between 0 and 64';
        }

        if ($request->city !== null && mb_strlen($request->city) > 32) {
            $validationErrors[] = 'city:length must be between 0 and 32';
        }

        if ($request->postcode !== null && mb_strlen($request->postcode) > 16) {
            $validationErrors[] = 'postcode:length must be between 0 and 16';
        }

        if ($request->address !== null && mb_strlen($request->address) > 255) {
            $validationErrors[] = 'address:length must be between 0 and 255';
        }

        if ($request->description !== null && mb_strlen($request->description) > 3000) {
            $validationErrors[] = 'description:length must be between 0 and 3000';
        }

        if ($validationErrors !== []) {
            throw new PMarketAPIException(implode('; ', $validationErrors));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function merchantCreatePayload(MerchantCreateRequest $request): array
    {
        return array_filter([
            'name' => $request->name,
            'email' => $request->email,
            'resellerName' => $request->resellerName,
            'contact' => $request->contact,
            'country' => $request->country,
            'phone' => $request->phone,
            'province' => $request->province,
            'city' => $request->city,
            'postcode' => $request->postcode,
            'address' => $request->address,
            'description' => $request->description,
            'createUserFlag' => $request->createUserFlag,
            'merchantCategoryNames' => $request->merchantCategoryNames,
            'entityAttributeValues' => $request->entityAttributeValues,
            'activateWhenCreate' => $request->activateWhenCreate,
        ], static fn ($value): bool => $value !== null && $value !== [] && $value !== '');
    }

    public function updateMerchant(int|string $merchantId, MerchantUpdateRequest $merchantUpdateRequest): Merchant
    {
        $merchantId = $this->assertPositiveInteger($merchantId, 'merchantId');
        $this->assertMerchantUpdateRequest($merchantUpdateRequest);

        /** @var Merchant $merchant */
        $merchant = $this->putResultData(
            endpoint: sprintf('/v1/3rdsys/merchants/%s', rawurlencode((string) $merchantId)),
            responseClass: Merchant::class,
            actionDescription: sprintf('update P Market merchant "%s"', $merchantId),
            body: $this->merchantUpdatePayload($merchantUpdateRequest),
        );

        return $merchant;
    }

    private function assertMerchantUpdateRequest(MerchantUpdateRequest $request): void
    {
        $validationErrors = [];

        if ($request->name !== null && trim($request->name) === '') {
            $validationErrors[] = 'name:may not be empty';
        }

        if ($request->name !== null && mb_strlen($request->name) > 128) {
            $validationErrors[] = 'name:length must be between 0 and 128';
        }

        if ($request->email !== null && $request->email !== '') {
            if (mb_strlen($request->email) > 255) {
                $validationErrors[] = 'email:length must be between 0 and 255';
            }

            if (filter_var($request->email, FILTER_VALIDATE_EMAIL) === false) {
                $validationErrors[] = 'email:not a well-formed email address';
            }
        }

        if ($request->resellerName !== null && mb_strlen($request->resellerName) > 64) {
            $validationErrors[] = 'resellerName:length must be between 0 and 64';
        }

        if ($request->contact !== null && mb_strlen($request->contact) > 64) {
            $validationErrors[] = 'contact:length must be between 0 and 64';
        }

        if ($request->country !== null && mb_strlen($request->country) > 64) {
            $validationErrors[] = 'country:length must be between 0 and 64';
        }

        if ($request->phone !== null && mb_strlen($request->phone) > 32) {
            $validationErrors[] = 'phone:length must be between 0 and 32';
        }

        if ($request->province !== null && mb_strlen($request->province) > 64) {
            $validationErrors[] = 'province:length must be between 0 and 64';
        }

        if ($request->postcode !== null && mb_strlen($request->postcode) > 16) {
            $validationErrors[] = 'postcode:length must be between 0 and 16';
        }

        if ($request->city !== null && mb_strlen($request->city) > 255) {
            $validationErrors[] = 'city:length must be between 0 and 255';
        }

        if ($request->address !== null && mb_strlen($request->address) > 255) {
            $validationErrors[] = 'address:length must be between 0 and 255';
        }

        if ($request->description !== null && mb_strlen($request->description) > 3000) {
            $validationErrors[] = 'description:length must be between 0 and 3000';
        }

        if ($validationErrors !== []) {
            throw new PMarketAPIException(implode('; ', $validationErrors));
        }
    }

    private function merchantUpdatePayload(MerchantUpdateRequest $request): array
    {
        return array_filter([
            'name' => $request->name,
            'email' => $request->email,
            'resellerName' => $request->resellerName,
            'contact' => $request->contact,
            'country' => $request->country,
            'phone' => $request->phone,
            'province' => $request->province,
            'city' => $request->city,
            'postcode' => $request->postcode,
            'address' => $request->address,
            'description' => $request->description,
            'createUserFlag' => $request->createUserFlag,
            'merchantCategoryNames' => $request->merchantCategoryNames,
            'entityAttributeValues' => $request->entityAttributeValues,
        ], static fn ($value): bool => $value !== null && $value !== [] && $value !== '');
    }

    public function activateMerchant(int|string $merchantId): bool
    {
        $merchantId = $this->assertPositiveInteger($merchantId, 'merchantId');

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/merchants/%s/active', rawurlencode((string) $merchantId)),
            actionDescription: sprintf('activate P Market merchant "%s"', $merchantId),
        );

        return true;
    }

    public function disableMerchant(int|string $merchantId): bool
    {
        $merchantId = $this->assertPositiveInteger($merchantId, 'merchantId');

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/merchants/%s/disable', rawurlencode((string) $merchantId)),
            actionDescription: sprintf('disable P Market merchant "%s"', $merchantId),
        );

        return true;
    }

    public function deleteMerchant(int|string $merchantId): bool
    {
        $merchantId = $this->assertPositiveInteger($merchantId, 'merchantId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rdsys/merchants/%s', rawurlencode((string) $merchantId)),
            actionDescription: sprintf('delete P Market merchant "%s"', $merchantId),
        );

        return true;
    }

    public function replaceMerchantEmail(int|string $merchantId, string $email, bool $createUser): bool
    {
        $merchantId = $this->assertPositiveInteger($merchantId, 'merchantId');
        $email = trim($email);

        if ($email === '') {
            throw new PMarketAPIException('email cannot be empty.');
        }

        if (mb_strlen($email) > 255) {
            throw new PMarketAPIException('Parameter email is too long, maxlength is 255!');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new PMarketAPIException('Parameter email format invalid!');
        }

        $this->emptyResult(
            method: 'POST',
            endpoint: sprintf('/v1/3rdsys/merchants/%s/replaceEmail', rawurlencode((string) $merchantId)),
            actionDescription: sprintf('replace P Market merchant "%s" email', $merchantId),
            headers: [
                'Content-Type' => 'application/json',
            ],
            body: [
                'email' => $email,
                'createUser' => $createUser,
            ],
        );

        return true;
    }

    private function deserializeMerchantSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): MerchantSearchResult {
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

        $businessCode = $decoded['businessCode'] ?? null;
        if ($businessCode !== 0) {
            throw new PMarketAPIException($this->resultErrorMessage($decoded, $actionDescription, $statusCode), (int) ($businessCode ?? 0));
        }

        $pageInfo = $decoded['pageInfo'] ?? $decoded;
        $dataSet = $pageInfo['dataSet'] ?? $pageInfo['dataset'] ?? [];

        $merchants = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $merchantData) {
            if (is_array($merchantData)) {
                $merchants[] = $this->serializer->denormalize($merchantData, Merchant::class);
            }
        }

        return new MerchantSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($merchants)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($merchants),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $merchants,
        );
    }

    private function getMerchantResultPage(
        string $endpoint,
        string $actionDescription,
        array $query = [],
        array $headers = [],
    ): MerchantSearchResult {
        $response = $this->request(
            method: 'GET',
            endpoint: $endpoint,
            query: $query,
            options: [
                'headers' => $this->defaultHeaders() + $headers,
            ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeMerchantSearchResult($response, $actionDescription);
    }
}
