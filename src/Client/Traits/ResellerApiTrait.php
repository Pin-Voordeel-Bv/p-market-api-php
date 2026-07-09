<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\Reseller;
use PinVandaag\PMarketAPI\Model\ResellerCreateRequest;
use PinVandaag\PMarketAPI\Model\ResellerRkiKey;
use PinVandaag\PMarketAPI\Model\ResellerRkiKeySearchResult;
use PinVandaag\PMarketAPI\Model\ResellerSearchResult;
use PinVandaag\PMarketAPI\Model\ResellerUpdateRequest;
use Psr\Http\Message\ResponseInterface;

trait ResellerApiTrait
{
    public function searchReseller(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $name = null,
        ?string $status = null,
        bool $includeEntityAttribute = false,
    ): ResellerSearchResult {
        $this->assertPage($pageNo, $pageSize);

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
            'includeEntityAttribute' => $this->boolString($includeEntityAttribute),
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeResellerOrderBy($orderBy);
        }

        if ($name !== null && $name !== '') {
            $query['name'] = $name;
        }

        if ($status !== null && $status !== '') {
            $query['status'] = $this->normalizeResellerStatus($status);
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/resellers',
            query: $query,
            options: [
                'headers' => $this->defaultHeaders(),
            ],
            actionDescription: 'search P Market resellers',
        );

        return $this->deserializeResellerSearchResult($response, 'search P Market resellers');
    }

    public function getReseller(int|string $resellerId): Reseller
    {
        $resellerId = $this->assertPositiveInteger($resellerId, 'resellerId');

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/resellers/%s', rawurlencode((string) $resellerId)),
            responseClass: Reseller::class,
            actionDescription: sprintf('get P Market reseller "%s"', $resellerId),
        );
    }

    public function createReseller(ResellerCreateRequest $request): Reseller
    {
        $this->assertResellerCreateRequest($request);

        return $this->postResultData(
            endpoint: '/v1/3rdsys/resellers',
            responseClass: Reseller::class,
            actionDescription: 'create P Market reseller',
            body: $this->resellerCreatePayload($request),
        );
    }

    public function updateReseller(int|string $resellerId, ResellerUpdateRequest $request): Reseller
    {
        $resellerId = $this->assertPositiveInteger($resellerId, 'resellerId');
        $this->assertResellerUpdateRequest($request);

        return $this->putResultData(
            endpoint: sprintf('/v1/3rdsys/resellers/%s', rawurlencode((string) $resellerId)),
            responseClass: Reseller::class,
            actionDescription: sprintf('update P Market reseller "%s"', $resellerId),
            body: $this->resellerUpdatePayload($request),
        );
    }

    public function activateReseller(int|string $resellerId): bool
    {
        $resellerId = $this->assertPositiveInteger($resellerId, 'resellerId');

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/resellers/%s/active', rawurlencode((string) $resellerId)),
            actionDescription: sprintf('activate P Market reseller "%s"', $resellerId),
        );

        return true;
    }

    public function disableReseller(int|string $resellerId): bool
    {
        $resellerId = $this->assertPositiveInteger($resellerId, 'resellerId');

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/resellers/%s/disable', rawurlencode((string) $resellerId)),
            actionDescription: sprintf('disable P Market reseller "%s"', $resellerId),
        );

        return true;
    }

    public function deleteReseller(int|string $resellerId): bool
    {
        $resellerId = $this->assertPositiveInteger($resellerId, 'resellerId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rdsys/resellers/%s', rawurlencode((string) $resellerId)),
            actionDescription: sprintf('delete P Market reseller "%s"', $resellerId),
        );

        return true;
    }

    public function replaceResellerEmail(int|string $resellerId, string $email): bool
    {
        $resellerId = $this->assertPositiveInteger($resellerId, 'resellerId');
        $email = trim($email);

        if ($email === '') {
            throw new PMarketAPIException('Parameter email format invalid!');
        }

        if (mb_strlen($email) > 255) {
            throw new PMarketAPIException('Parameter email is too long, maxlength is 255!');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new PMarketAPIException('Parameter email format invalid!');
        }

        $this->emptyResult(
            method: 'POST',
            endpoint: sprintf('/v1/3rdsys/resellers/%s/replaceEmail', rawurlencode((string) $resellerId)),
            actionDescription: sprintf('replace P Market reseller "%s" email', $resellerId),
            headers: ['Content-Type' => 'application/json'],
            body: [
                'email' => $email,
            ],
        );

        return true;
    }

    public function searchResellerRkiKeyList(
        int|string $resellerId,
        int $pageNo = 1,
        int $pageSize = 1000,
        ?string $rkiKey = null,
    ): ResellerRkiKeySearchResult {
        $resellerId = $this->assertPositiveInteger($resellerId, 'resellerId');

        if ($pageNo < 1) {
            throw new PMarketAPIException('pageNo:must be greater than or equal to 1');
        }

        if ($pageSize < 1) {
            throw new PMarketAPIException('pageSize:must be greater than or equal to 1');
        }

        if ($pageSize > 1000) {
            throw new PMarketAPIException('pageSize:must be less than or equal to 1000');
        }

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
        ];

        if ($rkiKey !== null && $rkiKey !== '') {
            $query['key'] = $rkiKey;
        }

        $response = $this->request(
            method: 'GET',
            endpoint: sprintf('/v1/3rdsys/resellers/%s/rki/template', rawurlencode((string) $resellerId)),
            query: $query,
            options: [
                'headers' => $this->defaultHeaders(),
            ],
            actionDescription: sprintf('search P Market reseller "%s" RKI keys', $resellerId),
        );

        return $this->deserializeResellerRkiKeySearchResult(
            $response,
            sprintf('search P Market reseller "%s" RKI keys', $resellerId),
        );
    }

    private function deserializeResellerSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): ResellerSearchResult {
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

        $resellers = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $resellerData) {
            if (is_array($resellerData)) {
                $resellers[] = $this->serializer->denormalize($resellerData, Reseller::class);
            }
        }

        return new ResellerSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($resellers)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($resellers),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $resellers,
        );
    }

    private function deserializeResellerRkiKeySearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): ResellerRkiKeySearchResult {
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

        $keys = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $keyData) {
            if (is_array($keyData)) {
                $keys[] = $this->serializer->denormalize($keyData, ResellerRkiKey::class);
            }
        }

        return new ResellerRkiKeySearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($keys)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($keys),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $keys,
        );
    }

    private function assertResellerCreateRequest(ResellerCreateRequest $request): void
    {
        $errors = [];

        foreach ([
            'name' => $request->name,
            'email' => $request->email,
            'country' => $this->normalizeCountryCode($request->country),
            'contact' => $request->contact,
            'phone' => $request->phone,
        ] as $field => $value) {
            if (trim($value) === '') {
                $errors[] = sprintf('%s:may not be empty', $field);
            }
        }

        $this->assertResellerCommonLengths($request, $errors);

        if ($request->email !== '' && filter_var($request->email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'email:not a well-formed email address';
        }

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function assertResellerUpdateRequest(ResellerUpdateRequest $request): void
    {
        $errors = [];

        foreach ([
            'name' => $request->name,
            'country' => $this->normalizeCountryCode($request->country),
            'contact' => $request->contact,
            'phone' => $request->phone,
        ] as $field => $value) {
            if (trim($value) === '') {
                $errors[] = sprintf('%s:may not be empty', $field);
            }
        }

        $this->assertResellerCommonLengths($request, $errors);

        if ($request->email !== null && $request->email !== '' && filter_var($request->email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'email:not a well-formed email address';
        }

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function assertResellerCommonLengths(object $request, array &$errors): void
    {
        foreach ([
            'name' => 64,
            'email' => 255,
            'country' => 64,
            'contact' => 64,
            'phone' => 32,
            'postcode' => 16,
            'address' => 255,
            'company' => 255,
            'parentResellerName' => 64,
        ] as $field => $maxLength) {
            if (!property_exists($request, $field)) {
                continue;
            }

            $value = $request->{$field};
            if ($value !== null && mb_strlen((string) $value) > $maxLength) {
                $errors[] = sprintf('%s:length must be between 0 and %d', $field, $maxLength);
            }
        }
    }

    private function resellerCreatePayload(ResellerCreateRequest $request): array
    {
        return array_filter([
            'name' => $request->name,
            'email' => $request->email,
            'country' => $this->normalizeCountryCode($request->country),
            'contact' => $request->contact,
            'phone' => $request->phone,
            'postcode' => $request->postcode,
            'address' => $request->address,
            'company' => $request->company,
            'parentResellerName' => $request->parentResellerName,
            'entityAttributeValues' => $request->entityAttributeValues,
            'activateWhenCreate' => $request->activateWhenCreate,
        ], static fn ($value): bool => $value !== null && $value !== [] && $value !== '');
    }

    private function resellerUpdatePayload(ResellerUpdateRequest $request): array
    {
        return array_filter([
            'name' => $request->name,
            'email' => $request->email,
            'country' => $this->normalizeCountryCode($request->country),
            'contact' => $request->contact,
            'phone' => $request->phone,
            'postcode' => $request->postcode,
            'address' => $request->address,
            'company' => $request->company,
            'parentResellerName' => $request->parentResellerName,
            'entityAttributeValues' => $request->entityAttributeValues,
        ], static fn ($value): bool => $value !== null && $value !== [] && $value !== '');
    }

    private function normalizeResellerOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'Name' => 'name',
            'Phone' => 'phone',
            'Contact' => 'contact',
            'name', 'phone', 'contact' => $orderBy,
            default => throw new PMarketAPIException('orderBy must be one of Name, Phone, Contact, name, phone or contact.'),
        };
    }

    private function normalizeResellerStatus(string $status): string
    {
        return match ($status) {
            'Active' => 'A',
            'Inactive', 'Pending', 'Pendding' => 'P',
            'Suspend' => 'S',
            'A', 'P', 'S' => $status,
            default => throw new PMarketAPIException('status must be one of Active, Inactive, Suspend, A, P or S.'),
        };
    }
}
