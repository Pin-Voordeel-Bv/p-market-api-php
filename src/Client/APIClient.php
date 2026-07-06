<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client;

use GuzzleHttp\ClientInterface;
use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\Merchant;
use PinVandaag\PMarketAPI\Model\MerchantCategory;
use PinVandaag\PMarketAPI\Model\MerchantCategoryRequest;
use PinVandaag\PMarketAPI\Model\MerchantCreateRequest;
use PinVandaag\PMarketAPI\Model\MerchantSearchResult;
use PinVandaag\PMarketAPI\Model\MerchantUpdateRequest;
use PinVandaag\PMarketAPI\Model\Terminal;
use PinVandaag\PMarketAPI\Model\TerminalCopyRequest;
use PinVandaag\PMarketAPI\Model\TerminalCreateRequest;
use PinVandaag\PMarketAPI\Model\TerminalGroup;
use PinVandaag\PMarketAPI\Model\TerminalGroupRequest;
use PinVandaag\PMarketAPI\Model\TerminalGroupSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalLog;
use PinVandaag\PMarketAPI\Model\TerminalLogDownloadTask;
use PinVandaag\PMarketAPI\Model\TerminalLogSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalNetwork;
use PinVandaag\PMarketAPI\Model\TerminalPed;
use PinVandaag\PMarketAPI\Model\TerminalSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalSystemUsage;
use PinVandaag\PMarketAPI\Model\TerminalUpdateRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareTrait;
use SensitiveParameter;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

final class APIClient
{
    use LoggerAwareTrait;

    private readonly SerializerInterface $serializer;
    private ?string $apiKey = null;
    private ?string $apiSecret = null;

    public function __construct(
        private readonly ClientInterface $client,
        private string $baseUri = '',
        ?SerializerInterface $serializer = null,
    ) {
        $this->baseUri = rtrim($this->baseUri, '/');
        $this->serializer = $serializer ?? new Serializer(
            [new ObjectNormalizer()],
            [new JsonEncoder()],
        );
    }

    public function setBaseUri(string $baseUri): self
    {
        $this->baseUri = rtrim($baseUri, '/');

        return $this;
    }

    public function setApiKey(#[SensitiveParameter] string $apiKey): self
    {
        if ($apiKey === '') {
            throw new PMarketAPIException('P Market API key cannot be empty.');
        }

        $this->apiKey = $apiKey;

        return $this;
    }

    public function setApiSecret(#[SensitiveParameter] string $apiSecret): self
    {
        if ($apiSecret === '') {
            throw new PMarketAPIException('P Market API secret cannot be empty.');
        }

        $this->apiSecret = $apiSecret;

        return $this;
    }

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

    /**
     * Search terminals by page.
     *
    * @throws PMarketAPIException
     */
    public function searchTerminal(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $status = null,
        ?string $snNameTID = null,
        ?string $resellerName = null,
        ?string $merchantName = null,
        bool $includeGeoLocation = false,
        bool $includeInstalledApks = false,
        bool $includeInstalledFirmware = false,
    ): TerminalSearchResult {
        $this->assertPage($pageNo, $pageSize);

        $query = [
            'limit' => (string) $pageSize,
            'pageNo' => (string) $pageNo,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeTerminalOrderBy($orderBy);
        }

        if ($resellerName !== null && $resellerName !== '') {
            $query['resellerName'] = $resellerName;
        }

        if ($merchantName !== null && $merchantName !== '') {
            $query['merchantName'] = $merchantName;
        }

        if ($status !== null && $status !== '') {
            $query['status'] = $this->normalizeTerminalStatus($status);
        }

        if ($snNameTID !== null && $snNameTID !== '') {
            $query['snNameTID'] = $snNameTID;
        }

        $query['includeGeoLocation'] = $this->boolString($includeGeoLocation);
        $query['includeInstalledFirmware'] = $this->boolString($includeInstalledFirmware);
        $query['includeInstalledApks'] = $this->boolString($includeInstalledApks);

        return $this->getResultPage(
            endpoint: '/v1/3rdsys/terminals',
            actionDescription: 'search P Market terminals',
            query: $query,
        );
    }

    /**
     * Retrieve a terminal by terminal ID.
     *
     * @throws PMarketAPIException
     */
    public function getTerminal(
        int|string $terminalId,
        bool $includeDetailInfoList = false,
        bool $includeInstalledApks = false,
        bool $includeInstalledFirmware = false,
        bool $includeMasterTerminal = false,
    ): Terminal {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');

        $query = [
            'includeDetailInfoList' => $this->boolString($includeDetailInfoList),
            'includeInstalledApks' => $this->boolString($includeInstalledApks),
            'includeMasterTerminal' => $this->boolString($includeMasterTerminal),
            'includeInstalledFirmware' => $this->boolString($includeInstalledFirmware),
        ];

        /** @var Terminal $terminal */
        $terminal = $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/terminals/%s', rawurlencode((string) $terminalId)),
            responseClass: Terminal::class,
            actionDescription: sprintf('retrieve P Market terminal "%s"', $terminalId),
            query: $query,
        );

        return $terminal;
    }

    /**
     * Create a terminal.
     *
     * @throws PMarketAPIException
     */
    public function createTerminal(TerminalCreateRequest $terminalCreateRequest): Terminal
    {
        $this->assertTerminalCreateRequest($terminalCreateRequest);

        $payload = array_filter([
            'name' => $terminalCreateRequest->name,
            'tid' => $terminalCreateRequest->tid,
            'serialNo' => $terminalCreateRequest->serialNo,
            'merchantName' => $terminalCreateRequest->merchantName,
            'resellerName' => $terminalCreateRequest->resellerName,
            'modelName' => $terminalCreateRequest->modelName,
            'location' => $terminalCreateRequest->location,
            'remark' => $terminalCreateRequest->remark,
            'status' => $terminalCreateRequest->status !== null && $terminalCreateRequest->status !== ''
                ? $this->normalizeTerminalCreateStatus($terminalCreateRequest->status)
                : null,
        ], static fn ($value): bool => $value !== null && $value !== '');

        /** @var Terminal $terminal */
        $terminal = $this->postResultData(
            endpoint: '/v1/3rdsys/terminals',
            responseClass: Terminal::class,
            actionDescription: 'create P Market terminal',
            body: $payload,
        );

        return $terminal;
    }

    /**
     * Update a terminal by terminal ID.
     *
     * @throws PMarketAPIException
     */
    public function updateTerminal(int|string $terminalId, TerminalUpdateRequest $terminalUpdateRequest): Terminal
    {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');
        $this->assertTerminalUpdateRequest($terminalUpdateRequest);

        /** @var Terminal $terminal */
        $terminal = $this->putResultData(
            endpoint: sprintf('/v1/3rdsys/terminals/%s', rawurlencode((string) $terminalId)),
            responseClass: Terminal::class,
            actionDescription: sprintf('update P Market terminal "%s"', $terminalId),
            body: $this->terminalUpdatePayload($terminalUpdateRequest),
        );

        return $terminal;
    }

    /**
     * Update a terminal by serial number.
     *
     * @throws PMarketAPIException
     */
    public function updateTerminalBySn(string $serialNo, TerminalUpdateRequest $terminalUpdateRequest): Terminal
    {
        $serialNo = trim($serialNo);
        if ($serialNo === '') {
            throw new PMarketAPIException('serialNo cannot be empty.');
        }

        $this->assertTerminalUpdateRequest($terminalUpdateRequest);

        /** @var Terminal $terminal */
        $terminal = $this->putResultData(
            endpoint: '/v1/3rdsys/terminal',
            responseClass: Terminal::class,
            actionDescription: sprintf('update P Market terminal by serialNo "%s"', $serialNo),
            query: [
                'serialNo' => $serialNo,
            ],
            body: $this->terminalUpdatePayload($terminalUpdateRequest),
        );

        return $terminal;
    }

    /**
     * Copy a terminal by source terminal ID.
     *
     * @throws PMarketAPIException
     */
    public function copyTerminal(TerminalCopyRequest $terminalCopyRequest): Terminal
    {
        $this->assertTerminalCopyRequest($terminalCopyRequest, false);

        /** @var Terminal $terminal */
        $terminal = $this->postResultData(
            endpoint: '/v1/3rdsys/terminals/copy',
            responseClass: Terminal::class,
            actionDescription: 'copy P Market terminal',
            body: $this->terminalCopyPayload($terminalCopyRequest),
        );

        return $terminal;
    }

    /**
     * Copy a terminal by source serial number.
     *
     * @throws PMarketAPIException
     */
    public function copyTerminalBySn(TerminalCopyRequest $terminalCopyRequest): Terminal
    {
        $this->assertTerminalCopyRequest($terminalCopyRequest, true);

        /** @var Terminal $terminal */
        $terminal = $this->postResultData(
            endpoint: '/v1/3rdsys/terminal/copy',
            responseClass: Terminal::class,
            actionDescription: 'copy P Market terminal by serialNo',
            body: $this->terminalCopyPayload($terminalCopyRequest),
        );

        return $terminal;
    }

    /**
     * Activate a terminal by terminal ID.
     *
     * @throws PMarketAPIException
     */
    public function activateTerminal(int|string $terminalId): bool
    {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/terminals/%s/active', rawurlencode((string) $terminalId)),
            actionDescription: sprintf('activate P Market terminal "%s"', $terminalId),
        );

        return true;
    }

    /**
     * Activate a terminal by serial number.
     *
     * @throws PMarketAPIException
     */
    public function activateTerminalBySn(string $serialNo): bool
    {
        $serialNo = trim($serialNo);
        if ($serialNo === '') {
            throw new PMarketAPIException('serialNo cannot be empty.');
        }

        $this->emptyResult(
            method: 'PUT',
            endpoint: '/v1/3rdsys/terminal/active',
            actionDescription: sprintf('activate P Market terminal by serialNo "%s"', $serialNo),
            query: ['serialNo' => $serialNo],
        );

        return true;
    }

    /**
     * Disable a terminal by terminal ID.
     *
     * @throws PMarketAPIException
     */
    public function disableTerminal(int|string $terminalId): bool
    {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/terminals/%s/disable', rawurlencode((string) $terminalId)),
            actionDescription: sprintf('disable P Market terminal "%s"', $terminalId),
        );

        return true;
    }

    /**
     * Disable a terminal by serial number.
     *
     * @throws PMarketAPIException
     */
    public function disableTerminalBySn(string $serialNo): bool
    {
        $serialNo = trim($serialNo);
        if ($serialNo === '') {
            throw new PMarketAPIException('serialNo cannot be empty.');
        }

        $this->emptyResult(
            method: 'PUT',
            endpoint: '/v1/3rdsys/terminal/disable',
            actionDescription: sprintf('disable P Market terminal by serialNo "%s"', $serialNo),
            query: ['serialNo' => $serialNo],
        );

        return true;
    }

    /**
     * Move a terminal by terminal ID.
     *
     * @throws PMarketAPIException
     */
    public function moveTerminal(int|string $terminalId, string $resellerName, string $merchantName): bool
    {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');
        $this->assertMoveTerminal($resellerName, $merchantName);

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/terminals/%s/move', rawurlencode((string) $terminalId)),
            actionDescription: sprintf('move P Market terminal "%s"', $terminalId),
            headers: [
                'Content-Type' => 'application/json',
            ],
            body: [
                'resellerName' => $resellerName,
                'merchantName' => $merchantName,
            ],
        );

        return true;
    }

    /**
     * Move a terminal by serial number.
     *
     * @throws PMarketAPIException
     */
    public function moveTerminalBySn(string $serialNo, string $resellerName, string $merchantName): bool
    {
        $serialNo = trim($serialNo);
        if ($serialNo === '') {
            throw new PMarketAPIException('serialNo cannot be empty.');
        }

        $this->assertMoveTerminal($resellerName, $merchantName);

        $this->emptyResult(
            method: 'PUT',
            endpoint: '/v1/3rdsys/terminal/move',
            actionDescription: sprintf('move P Market terminal by serialNo "%s"', $serialNo),
            query: [
                'serialNo' => $serialNo,
            ],
            headers: [
                'Content-Type' => 'application/json',
            ],
            body: [
                'resellerName' => $resellerName,
                'merchantName' => $merchantName,
            ],
        );

        return true;
    }

    /**
     * Delete a terminal by terminal ID.
     *
     * @throws PMarketAPIException
     */
    public function deleteTerminal(int|string $terminalId): bool
    {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');

        $this->deleteResult(
            endpoint: sprintf('/v1/3rdsys/terminals/%s', rawurlencode((string) $terminalId)),
            actionDescription: sprintf('delete P Market terminal "%s"', $terminalId),
        );

        return true;
    }

    /**
     * Delete a terminal by serial number.
     *
     * @throws PMarketAPIException
     */
    public function deleteTerminalBySn(string $serialNo): bool
    {
        $serialNo = trim($serialNo);
        if ($serialNo === '') {
            throw new PMarketAPIException('serialNo cannot be empty.');
        }

        $this->deleteResult(
            endpoint: '/v1/3rdsys/terminal',
            actionDescription: sprintf('delete P Market terminal by serialNo "%s"', $serialNo),
            query: [
                'serialNo' => $serialNo,
            ],
        );

        return true;
    }

    public function batchAddTerminalToGroup(array $terminalIds, array $groupIds): bool
    {
        if ($terminalIds === []) {
            throw new PMarketAPIException('terminalIds cannot be empty.');
        }

        if ($groupIds === []) {
            throw new PMarketAPIException('groupIds cannot be empty.');
        }

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/terminals/groups',
            actionDescription: 'batch add terminals to P Market terminal groups',
            headers: ['Content-Type' => 'application/json'],
            body: [
                'terminalIds' => array_map(
                    fn ($id) => $this->assertPositiveInteger($id, 'terminalId'),
                    $terminalIds
                ),
                'groupIds' => array_map(
                    fn ($id) => $this->assertPositiveInteger($id, 'groupId'),
                    $groupIds
                ),
            ],
        );

        return true;
    }

    public function batchAddTerminalToGroupBySn(array $serialNos, array $groupIds): bool
    {
        if ($serialNos === []) {
            throw new PMarketAPIException('serialNos cannot be empty.');
        }

        if ($groupIds === []) {
            throw new PMarketAPIException('groupIds cannot be empty.');
        }

        $serialNos = array_values(array_filter(array_map('trim', $serialNos)));

        if ($serialNos === []) {
            throw new PMarketAPIException('serialNos cannot be empty.');
        }

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/terminals/groups',
            actionDescription: 'batch add terminals to P Market terminal groups by serialNo',
            headers: ['Content-Type' => 'application/json'],
            body: [
                'serialNos' => $serialNos,
                'groupIds' => array_map(
                    fn ($id) => $this->assertPositiveInteger($id, 'groupId'),
                    $groupIds
                ),
            ],
        );

        return true;
    }

    public function getTerminalConfig(int|string $terminalId): array
    {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');

        return $this->getResultRawData(
            endpoint: sprintf('/v1/3rdsys/terminals/%s/config', rawurlencode((string) $terminalId)),
            actionDescription: sprintf('get terminal configuration "%s"', $terminalId),
        );
    }

    public function getTerminalConfigBySn(string $serialNo): array
    {
        $serialNo = trim($serialNo);

        if ($serialNo === '') {
            throw new PMarketAPIException('Parameter terminal SerialNo cannot be null!');
        }

        return $this->getResultRawData(
            endpoint: '/v1/3rdsys/terminal/config',
            actionDescription: sprintf('get terminal configuration by serialNo "%s"', $serialNo),
            query: [
                'serialNo' => $serialNo,
            ],
        );
    }

    public function updateTerminalConfig(
        int|string $terminalId,
        array $configuration
    ): bool
    {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf(
                '/v1/3rdsys/terminals/%s/config',
                rawurlencode((string) $terminalId)
            ),
            actionDescription: sprintf(
                'update terminal configuration "%s"',
                $terminalId
            ),
            headers: ['Content-Type' => 'application/json'],
            body: $configuration,
        );

        return true;
    }

    public function updateTerminalConfigBySn(
        string $serialNo,
        array $configuration
    ): bool
    {
        $serialNo = trim($serialNo);

        if ($serialNo === '') {
            throw new PMarketAPIException(
                'Parameter terminal serial no cannot be null!'
            );
        }

        $this->emptyResult(
            method: 'PUT',
            endpoint: '/v1/3rdsys/terminal/config',
            actionDescription: sprintf(
                'update terminal configuration by serialNo "%s"',
                $serialNo
            ),
            query: ['serialNo' => $serialNo],
            headers: ['Content-Type' => 'application/json'],
            body: $configuration,
        );

        return true;
    }

    public function pushCmdToTerminal(int|string $terminalId, string $command): bool
    {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');
        $command = $this->normalizeTerminalCommand($command);

        $this->emptyResult(
            method: 'POST',
            endpoint: sprintf('/v1/3rdsys/terminals/%s/operation', rawurlencode((string) $terminalId)),
            actionDescription: sprintf('push command to P Market terminal "%s"', $terminalId),
            query: [
                'command' => $command,
            ],
            headers: [
                'Content-Type' => 'application/json',
            ],
        );

        return true;
    }

    public function pushCmdToTerminalBySn(string $serialNo, string $command): bool
    {
        $serialNo = trim($serialNo);

        if ($serialNo === '') {
            throw new PMarketAPIException('serialNo cannot be empty.');
        }

        $command = $this->normalizeTerminalCommand($command);

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/terminal/operation',
            actionDescription: sprintf('push command to P Market terminal by serialNo "%s"', $serialNo),
            query: [
                'serialNo' => $serialNo,
                'command' => $command,
            ],
            headers: [
                'Content-Type' => 'application/json',
            ],
        );

        return true;
    }

    public function pushTerminalMessage(int|string $terminalId, string $title, string $content): bool
    {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');
        $payload = $this->terminalMessagePayload($title, $content);

        $this->emptyResult(
            method: 'POST',
            endpoint: sprintf('/v1/3rdsys/terminals/%s/push/message', rawurlencode((string) $terminalId)),
            actionDescription: sprintf('push message to P Market terminal "%s"', $terminalId),
            headers: ['Content-Type' => 'application/json'],
            body: $payload,
        );

        return true;
    }

    public function pushTerminalMessageBySn(string $serialNo, string $title, string $content): bool
    {
        $serialNo = trim($serialNo);
        if ($serialNo === '') {
            throw new PMarketAPIException('serialNo cannot be empty.');
        }

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/terminal/push/message',
            actionDescription: sprintf('push message to P Market terminal by serialNo "%s"', $serialNo),
            query: ['serialNo' => $serialNo],
            headers: ['Content-Type' => 'application/json'],
            body: $this->terminalMessagePayload($title, $content),
        );

        return true;
    }

    public function changeTerminalModel(int|string $terminalId, string $modelName): bool
    {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');
        $modelName = trim($modelName);

        if ($modelName === '') {
            throw new PMarketAPIException('modelName cannot be empty.');
        }

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/terminals/%s/model', rawurlencode((string) $terminalId)),
            actionDescription: sprintf('change model for P Market terminal "%s"', $terminalId),
            query: ['modelName' => $modelName],
        );

        return true;
    }

    public function changeTerminalModelBySn(string $serialNo, string $modelName): bool
    {
        $serialNo = trim($serialNo);
        $modelName = trim($modelName);

        if ($serialNo === '') {
            throw new PMarketAPIException('serialNo cannot be empty.');
        }

        if ($modelName === '') {
            throw new PMarketAPIException('modelName cannot be empty.');
        }

        $this->emptyResult(
            method: 'PUT',
            endpoint: '/v1/3rdsys/terminal/model',
            actionDescription: sprintf('change model for P Market terminal by serialNo "%s"', $serialNo),
            query: [
                'serialNo' => $serialNo,
                'modelName' => $modelName,
            ],
        );

        return true;
    }

    public function pushTerminalSetLauncherAction(int|string $terminalId, string $packageName): bool
    {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');
        $packageName = trim($packageName);

        if ($packageName === '') {
            throw new PMarketAPIException('packageName cannot be empty.');
        }

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/terminals/%s/launcher', rawurlencode((string) $terminalId)),
            actionDescription: sprintf('set launcher for P Market terminal "%s"', $terminalId),
            query: ['packageName' => $packageName],
        );

        return true;
    }

    public function pushTerminalSetLauncherActionBySn(string $serialNo, string $packageName): bool
    {
        $serialNo = trim($serialNo);
        $packageName = trim($packageName);

        if ($serialNo === '') {
            throw new PMarketAPIException('serialNo cannot be empty.');
        }

        if ($packageName === '') {
            throw new PMarketAPIException('packageName cannot be empty.');
        }

        $this->emptyResult(
            method: 'PUT',
            endpoint: '/v1/3rdsys/terminal/launcher',
            actionDescription: sprintf('set launcher for P Market terminal by serialNo "%s"', $serialNo),
            query: [
                'serialNo' => $serialNo,
                'packageName' => $packageName,
            ],
        );

        return true;
    }

    private function normalizeTerminalCommand(string $command): string
    {
        return match ($command) {
            'Lock', 'Unlock', 'Restart' => $command,
            'lock' => 'Lock',
            'unlock' => 'Unlock',
            'restart', 'reboot' => 'Restart',
            default => throw new PMarketAPIException('command must be one of Lock, Unlock or Restart.'),
        };
    }

    private function terminalMessagePayload(string $title, string $content): array
    {
        $title = trim($title);
        $content = trim($content);

        if ($title === '') {
            throw new PMarketAPIException('Push message title cannot be empty.');
        }

        if (mb_strlen($title) > 64) {
            throw new PMarketAPIException('Push message title is too long.');
        }

        if ($content === '') {
            throw new PMarketAPIException('Push message content cannot be empty.');
        }

        if (mb_strlen($content) > 256) {
            throw new PMarketAPIException('Push message content is too long.');
        }

        return [
            'title' => $title,
            'content' => $content,
        ];
    }

    public function getTerminalNetwork(?string $serialNo = null, ?string $tid = null): TerminalNetwork
    {
        $serialNo = trim((string) $serialNo);
        $tid = trim((string) $tid);

        if ($serialNo === '' && $tid === '') {
            throw new PMarketAPIException('The property serialNo and tid in request cannot be blank at same time!');
        }

        return $this->getResultData(
            endpoint: '/v1/3rdsys/terminals/network',
            responseClass: TerminalNetwork::class,
            actionDescription: 'get P Market terminal network information',
            query: array_filter([
                'serialNo' => $serialNo,
                'tid' => $tid,
            ], static fn ($value): bool => $value !== ''),
        );
    }

    public function getTerminalPed(int|string $terminalId): TerminalPed
    {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/terminals/%s/ped', rawurlencode((string) $terminalId)),
            responseClass: TerminalPed::class,
            actionDescription: sprintf('get P Market terminal PED "%s"', $terminalId),
        );
    }

    public function getTerminalPedBySn(string $serialNo): TerminalPed
    {
        $serialNo = trim($serialNo);

        if ($serialNo === '') {
            throw new PMarketAPIException('Parameter terminal serialNo cannot be null!');
        }

        return $this->getResultData(
            endpoint: '/v1/3rdsys/terminal/ped',
            responseClass: TerminalPed::class,
            actionDescription: sprintf('get P Market terminal PED by serialNo "%s"', $serialNo),
            query: [
                'serialNo' => $serialNo,
            ],
        );
    }

    public function getTerminalSystemUsageById(int|string $terminalId): TerminalSystemUsage
    {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/terminals/%s/system/usage', rawurlencode((string) $terminalId)),
            responseClass: TerminalSystemUsage::class,
            actionDescription: sprintf('get P Market terminal system usage "%s"', $terminalId),
        );
    }

    public function getTerminalSystemUsageBySn(string $serialNo): TerminalSystemUsage
    {
        $serialNo = trim($serialNo);

        if ($serialNo === '') {
            throw new PMarketAPIException('Parameter terminal serialNo cannot be null!');
        }

        return $this->getResultData(
            endpoint: '/v1/3rdsys/terminal/system/usage',
            responseClass: TerminalSystemUsage::class,
            actionDescription: sprintf('get P Market terminal system usage by serialNo "%s"', $serialNo),
            query: [
                'serialNo' => $serialNo,
            ],
        );
    }

    public function collectTerminalLog(
        int|string $terminalId,
        string $type,
        ?string $beginDate = null,
        ?string $endDate = null,
    ): bool {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');

        $body = [
            'type' => $type,
        ];

        if ($beginDate !== null) {
            $body['beginDate'] = $beginDate;
        }

        if ($endDate !== null) {
            $body['endDate'] = $endDate;
        }

        $this->emptyResult(
            method: 'POST',
            endpoint: sprintf(
                '/v1/3rdsys/terminals/%s/collect/log',
                rawurlencode((string) $terminalId)
            ),
            actionDescription: sprintf(
                'collect log for P Market terminal "%s"',
                $terminalId
            ),
            headers: ['Content-Type' => 'application/json'],
            body: $body,
        );

        return true;
    }

    public function collectTerminalLogBySn(
        string $serialNo,
        string $type,
        ?string $beginDate = null,
        ?string $endDate = null,
    ): bool {
        $serialNo = trim($serialNo);

        if ($serialNo === '') {
            throw new PMarketAPIException('Parameter terminal serialNo cannot be null!');
        }

        $body = [
            'serialNo' => $serialNo,
            'type' => $type,
        ];

        if ($beginDate !== null) {
            $body['beginDate'] = $beginDate;
        }

        if ($endDate !== null) {
            $body['endDate'] = $endDate;
        }

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/terminal/collect/log',
            actionDescription: sprintf(
                'collect log for terminal by serialNo"%s"',
                $serialNo
            ),
            query: ['serialNo' => $serialNo],
            headers: ['Content-Type' => 'application/json'],
            body: $body,
        );

        return true;
    }

    public function searchTerminalLog(
        int|string $terminalId,
        int $pageNo = 1,
        int $pageSize = 10,
    ): TerminalLogSearchResult {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');

        return $this->getTerminalLogSearchResult(
            endpoint: sprintf('/v1/3rdsys/terminals/%s/logs', rawurlencode((string) $terminalId)),
            actionDescription: sprintf('search terminal logs "%s"', $terminalId),
            query: [
                'pageNo' => (string) $pageNo,
                'pageSize' => (string) $pageSize,
            ],
        );
    }

    public function searchTerminalLogBySn(
        string $serialNo,
        int $pageNo = 1,
        int $pageSize = 10,
    ): TerminalLogSearchResult {
        $serialNo = trim($serialNo);

        if ($serialNo === '') {
            throw new PMarketAPIException('Parameter terminal serialNo cannot be null!');
        }

        return $this->getTerminalLogSearchResult(
            endpoint: '/v1/3rdsys/terminal/logs',
            actionDescription: sprintf('search terminal logs "%s"', $serialNo),
            query: [
                'serialNo' => $serialNo,
                'pageNo' => (string) $pageNo,
                'pageSize' => (string) $pageSize,
            ],
        );
    }

    private function getTerminalLogSearchResult(
        string $endpoint,
        string $actionDescription,
        array $query = [],
    ): TerminalLogSearchResult {
        $response = $this->request(
            method: 'GET',
            endpoint: $endpoint,
            query: $query,
            options: [
                'headers' => $this->defaultHeaders(),
            ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeTerminalLogSearchResult($response, $actionDescription);
    }

    private function deserializeTerminalLogSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): TerminalLogSearchResult {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new PMarketAPIException(sprintf('Could not decode P Market response for %s.', $actionDescription));
        }

        if (($decoded['businessCode'] ?? null) !== 0) {
            throw new PMarketAPIException(
                $this->resultErrorMessage($decoded, $actionDescription, $statusCode),
                (int) ($decoded['businessCode'] ?? 0)
            );
        }

        $pageInfo = $decoded['pageInfo'] ?? $decoded;
        $dataSet = $pageInfo['dataSet'] ?? $pageInfo['dataset'] ?? [];

        $logs = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $logData) {
            if (is_array($logData)) {
                $logs[] = $this->serializer->denormalize($logData, TerminalLog::class);
            }
        }

        return new TerminalLogSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($logs)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($logs),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $logs,
        );
    }

    public function downloadTerminalLog(
        int|string $terminalId,
        int|string $terminalLogId,
    ): TerminalLogDownloadTask {
        $terminalId = $this->assertPositiveInteger($terminalId, 'terminalId');
        $terminalLogId = $this->assertPositiveInteger($terminalLogId, 'terminalLogId');

        return $this->getResultData(
            endpoint: sprintf(
                '/v1/3rdsys/terminals/%s/logs/%s/download-task',
                rawurlencode((string) $terminalId),
                rawurlencode((string) $terminalLogId)
            ),
            responseClass: TerminalLogDownloadTask::class,
            actionDescription: sprintf(
                'download terminal log "%s"',
                $terminalLogId
            ),
        );
    }

    public function downloadTerminalLogBySn(
        string $serialNo,
        int|string $terminalLogId,
    ): TerminalLogDownloadTask {
        $serialNo = trim($serialNo);

        if ($serialNo === '') {
            throw new PMarketAPIException('Parameter terminal serialNo cannot be null!');
        }

        $terminalLogId = $this->assertPositiveInteger($terminalLogId, 'terminalLogId');

        return $this->getResultData(
            endpoint: sprintf(
                '/v1/3rdsys/terminal/logs/%s/download-task',
                rawurlencode((string) $terminalLogId)
            ),
            responseClass: TerminalLogDownloadTask::class,
            actionDescription: sprintf(
                'download terminal log "%s"',
                $terminalLogId
            ),
            query: [
                'serialNo' => $serialNo,
            ],
        );
    }

    public function searchTerminalGroup(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $status = null,
        ?string $name = null,
        ?string $resellerNames = null,
        ?string $modelNames = null,
        ?bool $isDynamic = null,
    ): TerminalGroupSearchResult {
        $this->assertPage($pageNo, $pageSize);

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
        ];

        foreach ([
            'orderBy' => $orderBy,
            'status' => $status,
            'name' => $name,
            'resellerNames' => $resellerNames,
            'modelNames' => $modelNames,
        ] as $key => $value) {
            if ($value !== null && $value !== '') {
                $query[$key] = $value;
            }
        }

        if ($isDynamic !== null) {
            $query['isDynamic'] = $this->boolString($isDynamic);
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/terminalGroups',
            query: $query,
            options: ['headers' => $this->defaultHeaders()],
            actionDescription: 'search P Market terminal groups',
        );

        return $this->deserializeTerminalGroupSearchResult($response, 'search P Market terminal groups');
    }

    public function getTerminalGroup(int|string $groupId): TerminalGroup
    {
        $groupId = $this->assertPositiveInteger($groupId, 'groupId');

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/terminalGroups/%s', rawurlencode((string) $groupId)),
            responseClass: TerminalGroup::class,
            actionDescription: sprintf('get P Market terminal group "%s"', $groupId),
        );
    }

    public function createTerminalGroup(TerminalGroupRequest $request): TerminalGroup
    {
        $this->assertTerminalGroupCreateRequest($request);

        return $this->postResultData(
            endpoint: '/v1/3rdsys/terminalGroups',
            responseClass: TerminalGroup::class,
            actionDescription: 'create P Market terminal group',
            body: $this->terminalGroupPayload($request),
        );
    }

    public function updateTerminalGroup(int|string $groupId, TerminalGroupRequest $request): TerminalGroup
    {
        $groupId = $this->assertPositiveInteger($groupId, 'groupId');

        return $this->putResultData(
            endpoint: sprintf('/v1/3rdsys/terminalGroups/%s', rawurlencode((string) $groupId)),
            responseClass: TerminalGroup::class,
            actionDescription: sprintf('update P Market terminal group "%s"', $groupId),
            body: $this->terminalGroupPayload($request),
        );
    }

    public function activateTerminalGroup(int|string $groupId): bool
    {
        $groupId = $this->assertPositiveInteger($groupId, 'groupId');

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/terminalGroups/%s/active', rawurlencode((string) $groupId)),
            actionDescription: sprintf('activate P Market terminal group "%s"', $groupId),
        );

        return true;
    }

    public function disableTerminalGroup(int|string $groupId): bool
    {
        $groupId = $this->assertPositiveInteger($groupId, 'groupId');

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/terminalGroups/%s/disable', rawurlencode((string) $groupId)),
            actionDescription: sprintf('disable P Market terminal group "%s"', $groupId),
        );

        return true;
    }

    public function deleteTerminalGroup(int|string $groupId): bool
    {
        $groupId = $this->assertPositiveInteger($groupId, 'groupId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rdsys/terminalGroups/%s', rawurlencode((string) $groupId)),
            actionDescription: sprintf('delete P Market terminal group "%s"', $groupId),
        );

        return true;
    }

    public function addTerminalToGroup(int|string $groupId, array $terminalIds): bool
    {
        $groupId = $this->assertPositiveInteger($groupId, 'groupId');

        if ($terminalIds === []) {
            throw new PMarketAPIException('Terminal Ids is mandatory');
        }

        $ids = array_map(fn ($id) => $this->assertPositiveInteger($id, 'terminalId'), $terminalIds);

        $this->emptyResult(
            method: 'POST',
            endpoint: sprintf('/v1/3rdsys/terminalGroups/%s/terminals', rawurlencode((string) $groupId)),
            actionDescription: sprintf('add terminals to P Market terminal group "%s"', $groupId),
            headers: ['Content-Type' => 'application/json'],
            body: $ids,
        );

        return true;
    }

    public function removeTerminalOutGroup(int|string $groupId, array $terminalIds): bool
    {
        $groupId = $this->assertPositiveInteger($groupId, 'groupId');

        if ($terminalIds === []) {
            throw new PMarketAPIException('Terminal Ids is mandatory');
        }

        $ids = array_map(fn ($id) => $this->assertPositiveInteger($id, 'terminalId'), $terminalIds);

        $this->emptyResult(
            method: 'POST',
            endpoint: sprintf('/v1/3rdsys/terminalGroups/%s/terminals', rawurlencode((string) $groupId)),
            actionDescription: sprintf('remove terminals from P Market terminal group "%s"', $groupId),
            headers: ['Content-Type' => 'application/json'],
            body: $ids,
        );

        return true;
    }

    private function deserializeTerminalGroupSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): TerminalGroupSearchResult {
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

        $groups = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $groupData) {
            if (is_array($groupData)) {
                $groups[] = $this->serializer->denormalize($groupData, TerminalGroup::class);
            }
        }

        return new TerminalGroupSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($groups)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($groups),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $groups,
        );
    }

    private function assertTerminalGroupCreateRequest(TerminalGroupRequest $request): void
    {
        $errors = [];

        if (trim((string) $request->name) === '') {
            $errors[] = 'name:may not be empty';
        }

        if (trim((string) $request->modelName) === '') {
            $errors[] = 'modelName:may not be empty';
        }

        if (trim((string) $request->resellerName) === '') {
            $errors[] = 'resellerName:may not be empty';
        }

        if ($request->name !== null && mb_strlen($request->name) > 64) {
            $errors[] = 'name:length must be between 0 and 64';
        }

        if ($request->description !== null && mb_strlen($request->description) > 255) {
            $errors[] = 'description:length must be between 0 and 255';
        }

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function terminalGroupPayload(TerminalGroupRequest $request): array
    {
        return array_filter([
            'name' => $request->name,
            'modelName' => $request->modelName,
            'resellerName' => $request->resellerName,
            'description' => $request->description,
            'status' => $request->status,
            'dynamic' => $request->dynamic,
            'containSubResellerTerminal' => $request->containSubResellerTerminal,
            'merchantNameList' => $request->merchantNameList,
        ], static fn ($value): bool => $value !== null && $value !== [] && $value !== '');
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $responseClass
     * @param array<string, string> $query
     * @param array<string, string> $headers
     *
     * @return T
     *
     * @throws PMarketAPIException
     */
    private function getResultData(
        string $endpoint,
        string $responseClass,
        string $actionDescription,
        array $query = [],
        array $headers = [],
    ): object {
        $response = $this->request(
            method: 'GET',
            endpoint: $endpoint,
            query: $query,
            options: [
                'headers' => $this->defaultHeaders() + $headers,
            ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeResultData($response, $responseClass, $actionDescription);
    }

    private function getResultRawData(
        string $endpoint,
        string $actionDescription,
        array $query = [],
        array $headers = [],
    ): array {
        $response = $this->request(
            method: 'GET',
            endpoint: $endpoint,
            query: $query,
            options: [
                'headers' => $this->defaultHeaders() + $headers,
            ],
            actionDescription: $actionDescription,
        );

        $decoded = json_decode((string) $response->getBody(), true);

        if (!is_array($decoded)) {
            throw new PMarketAPIException(sprintf('Could not decode P Market response for %s.', $actionDescription));
        }

        if (($decoded['businessCode'] ?? null) !== 0) {
            throw new PMarketAPIException(
                $this->resultErrorMessage($decoded, $actionDescription, $response->getStatusCode()),
                (int) ($decoded['businessCode'] ?? 0)
            );
        }

        return is_array($decoded['data'] ?? null) ? $decoded['data'] : [];
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $responseClass
     * @param array<string, mixed> $body
     *
     * @return T
     *
     * @throws PMarketAPIException
     */
    private function postResultData(
        string $endpoint,
        string $responseClass,
        string $actionDescription,
        array $query = [],
        array $body = [],
        array $headers = [],
    ): object {
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

        return $this->deserializeResultData($response, $responseClass, $actionDescription);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $responseClass
     * @param array<string, mixed> $body
     *
     * @return T
     *
     * @throws PMarketAPIException
     */
    private function putResultData(
        string $endpoint,
        string $responseClass,
        string $actionDescription,
        array $query = [],
        array $body = [],
        array $headers = [],
    ): object {
        $response = $this->request(
            method: 'PUT',
            endpoint: $endpoint,
            query: $query,
            options: [
                'headers' => $this->defaultHeaders() + ['Content-Type' => 'application/json'] + $headers,
                'json' => $body,
            ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeResultData($response, $responseClass, $actionDescription);
    }

    /**
     * @param array<string, string> $query
     * @param array<string, string> $headers
     *
     * @throws PMarketAPIException
     */
    private function deleteResult(
        string $endpoint,
        string $actionDescription,
        array $query = [],
        array $headers = [],
    ): void {
        $response = $this->request(
            method: 'DELETE',
            endpoint: $endpoint,
            query: $query,
            options: [
                'headers' => $this->defaultHeaders() + $headers,
            ],
            actionDescription: $actionDescription,
        );

        $this->deserializeEmptyResult($response, $actionDescription);
    }

    private function emptyResult(
        string $method,
        string $endpoint,
        string $actionDescription,
        array $query = [],
        array $headers = [],
        array $body = [],
    ): void {
        $options = [
            'headers' => $this->defaultHeaders() + $headers,
        ];

        if ($body !== []) {
            $options['json'] = $body;
        }

        $response = $this->request(
            method: $method,
            endpoint: $endpoint,
            query: $query,
            options: $options,
            actionDescription: $actionDescription,
        );

        $this->deserializeEmptyResult($response, $actionDescription);
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

    private function getResultPage(
        string $endpoint,
        string $actionDescription,
        array $query = [],
        array $headers = [],
    ): TerminalSearchResult {
        $response = $this->request(
            method: 'GET',
            endpoint: $endpoint,
            query: $query,
            options: [
                'headers' => $this->defaultHeaders() + $headers,
            ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeTerminalSearchResult($response, $actionDescription);
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, string> $query
     *
     * @throws PMarketAPIException
     */
    private function request(
        string $method,
        string $endpoint,
        array $query,
        array $options,
        string $actionDescription,
    ): ResponseInterface {
        [$signedQueryString, $signature] = $this->signedQueryAndSignature($query);
        $options['headers'] = ($options['headers'] ?? []) + ['signature' => $signature];

        try {
            return $this->client->request(
                $method,
                $this->uri($endpoint),
                $options + [
                    'query' => $signedQueryString,
                    'connect_timeout' => 8.0,
                    'http_errors' => false,
                    'timeout' => 25.0,
                    'verify' => true,
                ],
            );
        } catch (Throwable $exception) {
            throw new PMarketAPIException(sprintf('Could not %s.', $actionDescription), 0, $exception);
        }
    }

    private function errorMessageFromResponseBody(
        string $body,
        string $actionDescription,
        int $statusCode,
    ): string {
        $trimmedBody = trim($body);

        if ($trimmedBody === '') {
            return sprintf(
                'P Market request failed while trying to %s with HTTP %d.',
                $actionDescription,
                $statusCode,
            );
        }

        $decoded = json_decode($trimmedBody, true);

        if (is_array($decoded)) {
            $message = $decoded['message'] ?? null;
            if (is_string($message) && $message !== '') {
                $nested = json_decode($message, true);
                if (is_array($nested)) {
                    return $this->resultErrorMessage($nested, $actionDescription, $statusCode);
                }

                return $message;
            }

            return $this->resultErrorMessage($decoded, $actionDescription, $statusCode);
        }

        return $trimmedBody;
    }

    private function uri(string $endpoint): string
    {
        return $this->baseUri . '/' . ltrim($endpoint, '/');
    }

    /**
     * @return array<string, string>
     */
    private function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'SDK-Language' => 'Java',
            'SDK-Version' => '10.2.1',
            'Accept-Language' => 'en',
            'Time-Zone' => date_default_timezone_get() ?: 'UTC',
        ];
    }

    /**
     * P Market signs the complete URL query string exactly like the Java SDK:
     * add sysKey + timestamp as query params, build name=urlencoded(value) joined by &, then HMAC-SHA256 with apiSecret.
     * The resulting upper-case hex digest is sent in the `signature` header, not as X-Api-Key.
     *
     * @param array<string, string> $query
     *
     * @return array{0: string, 1: string}
     */
    private function signedQueryAndSignature(array $query): array
    {
        if ($this->apiKey === null || $this->apiKey === '') {
            throw new PMarketAPIException('P Market API key has not been configured.');
        }

        if ($this->apiSecret === null || $this->apiSecret === '') {
            throw new PMarketAPIException('P Market API secret has not been configured.');
        }

        $query['sysKey'] = $this->apiKey;
        $query['timestamp'] = (string) (int) floor(microtime(true) * 1000);

        $queryString = $this->javaBuildQuery($query);
        $signature = strtoupper(hash_hmac('sha256', $queryString, $this->apiSecret));

        return [$queryString, $signature];
    }

    /**
     * Java's ThirdPartySysHttpUtils.buildQuery() does not sort here; it keeps insertion order.
     * It URL-encodes values with Java URLEncoder semantics, where spaces become `+`.
     *
     * @param array<string, string> $query
     */
    private function javaBuildQuery(array $query): string
    {
        $parts = [];
        foreach ($query as $name => $value) {
            if ($name === '' || $value === '') {
                continue;
            }

            $parts[] = $name . '=' . urlencode($value);
        }

        return implode('&', $parts);
    }

    private function deserializeTerminalSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): TerminalSearchResult {
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
        if (!is_array($pageInfo)) {
            throw new PMarketAPIException(sprintf('P Market returned empty pageInfo while trying to %s.', $actionDescription));
        }

        $dataSet = $pageInfo['dataSet'] ?? $pageInfo['dataset'] ?? [];
        if (!is_array($dataSet)) {
            $dataSet = [];
        }

        $terminals = [];
        foreach ($dataSet as $terminalData) {
            if (!is_array($terminalData)) {
                continue;
            }

            $terminals[] = $this->serializer->denormalize($terminalData, Terminal::class);
        }

        return new TerminalSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($terminals)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($terminals),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $terminals,
            orderBy: isset($pageInfo['orderBy']) ? (string) $pageInfo['orderBy'] : null,
        );
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $responseClass
     *
     * @return T
     *
     * @throws PMarketAPIException
     */
    private function deserializeResultData(
        ResponseInterface $response,
        string $responseClass,
        string $actionDescription,
    ): object {
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

        if (!array_key_exists('data', $decoded) || $decoded['data'] === null) {
            throw new PMarketAPIException(sprintf('P Market returned empty data while trying to %s.', $actionDescription));
        }

        try {
            /** @var T $result */
            $result = $this->serializer->denormalize($decoded['data'], $responseClass);
        } catch (SerializerException $exception) {
            throw new PMarketAPIException(
                sprintf('Could not deserialize P Market response for %s.', $actionDescription),
                0,
                $exception
            );
        }

        return $result;
    }

    /**
     * @throws PMarketAPIException
     */
    private function deserializeEmptyResult(
        ResponseInterface $response,
        string $actionDescription,
    ): void {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new PMarketAPIException(
                $this->errorMessageFromResponseBody($body, $actionDescription, $statusCode),
                $statusCode,
            );
        }

        $trimmedBody = trim($body);
        if ($trimmedBody === '') {
            return;
        }

        $decoded = json_decode($trimmedBody, true);
        if (!is_array($decoded)) {
            throw new PMarketAPIException(sprintf('Could not decode P Market response for %s.', $actionDescription));
        }

        $businessCode = $decoded['businessCode'] ?? null;
        if ($businessCode !== 0) {
            throw new PMarketAPIException(
                $this->resultErrorMessage($decoded, $actionDescription, $statusCode),
                (int) ($businessCode ?? 0),
            );
        }
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private function resultErrorMessage(array $decoded, string $actionDescription, int $statusCode): string
    {
        if (!empty($decoded['validationErrors']) && is_array($decoded['validationErrors'])) {
            return implode('; ', array_map('strval', $decoded['validationErrors']));
        }

        $businessCode = $decoded['businessCode'] ?? null;
        $message = $decoded['message'] ?? null;

        if (is_scalar($businessCode) && is_scalar($message)) {
            return sprintf('P Market businessCode %s: %s', (string) $businessCode, (string) $message);
        }

        if (is_scalar($message)) {
            return (string) $message;
        }

        return sprintf(
            'P Market request failed while trying to %s with HTTP %d.',
            $actionDescription,
            $statusCode,
        );
    }

    private function assertPositiveInteger(mixed $value, string $fieldName): int
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === null || $value === '' || filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            throw new PMarketAPIException(sprintf('%s cannot be null and cannot be less than 1.', $fieldName));
        }

        return (int) $value;
    }

    private function assertPage(int $pageNo, int $pageSize): void
    {
        $validationErrors = [];

        if ($pageNo < 1) {
            $validationErrors[] = 'pageNo:must be greater than or equal to 1';
        }

        if ($pageSize < 1) {
            $validationErrors[] = 'pageSize:must be greater than or equal to 1';
        }

        if ($pageSize > 100) {
            $validationErrors[] = 'pageSize:must be less than or equal to 100';
        }

        if ($validationErrors !== []) {
            throw new PMarketAPIException(implode('; ', $validationErrors));
        }
    }

    private function assertTerminalCreateRequest(TerminalCreateRequest $request): void
    {
        $validationErrors = [];

        if (trim($request->name) === '') {
            $validationErrors[] = 'name:may not be empty';
        }

        if (mb_strlen($request->name) > 64) {
            $validationErrors[] = 'name:length must be between 0 and 64';
        }

        if ($request->resellerName === null || trim($request->resellerName) === '') {
            $validationErrors[] = 'resellerName:may not be empty';
        }

        if ($request->resellerName !== null && mb_strlen($request->resellerName) > 64) {
            $validationErrors[] = 'resellerName:length must be between 0 and 64';
        }

        if ($request->modelName === null || trim($request->modelName) === '') {
            $validationErrors[] = 'modelName:may not be empty';
        }

        if ($request->modelName !== null && mb_strlen($request->modelName) > 64) {
            $validationErrors[] = 'modelName:length must be between 0 and 64';
        }

        if ($request->tid !== null && $request->tid !== '') {
            $tidLength = mb_strlen($request->tid);
            if ($tidLength < 8 || $tidLength > 15) {
                $validationErrors[] = 'tid:length must be between 8 and 15';
            }
        }

        if ($request->serialNo !== null && $request->serialNo !== '') {
            if (mb_strlen($request->serialNo) > 32) {
                $validationErrors[] = 'serialNo:length must be between 0 and 32';
            }
        }

        if ($request->merchantName !== null && mb_strlen($request->merchantName) > 64) {
            $validationErrors[] = 'merchantName:length must be between 0 and 64';
        }

        if ($request->location !== null && mb_strlen($request->location) > 32) {
            $validationErrors[] = 'location:length must be between 0 and 32';
        }

        if ($request->remark !== null && mb_strlen($request->remark) > 500) {
            $validationErrors[] = 'remark:length must be between 0 and 500';
        }

        $status = $request->status !== null && $request->status !== ''
            ? $this->normalizeTerminalCreateStatus($request->status)
            : null;

        if ($status === 'A') {
            if ($request->serialNo === null || trim($request->serialNo) === '') {
                $validationErrors[] = 'serialNo:may not be empty when status is Active';
            }

            if ($request->merchantName === null || trim($request->merchantName) === '') {
                $validationErrors[] = 'merchantName:may not be empty when status is Active';
            }
        }

        if ($validationErrors !== []) {
            throw new PMarketAPIException(implode('; ', $validationErrors));
        }
    }

    private function normalizeMerchantOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'Name' => 'name',
            'Phone' => 'phone',
            'Contact' => 'contact',
            'name', 'phone', 'contact' => $orderBy,
            default => throw new PMarketAPIException('orderBy must be one of Name, Phone, Contact, name, phone or contact.'),
        };
    }

    private function normalizeMerchantStatus(string $status): string
    {
        return match ($status) {
            'Active' => 'A',
            'Inactive', 'Pending', 'Pendding' => 'P',
            'Suspend' => 'S',
            'A', 'P', 'S' => $status,
            default => throw new PMarketAPIException('status must be one of Active, Inactive, Suspend, A, P or S.'),
        };
    }

    private function normalizeTerminalCreateStatus(string $status): string
    {
        return match ($status) {
            'Active' => 'A',
            'Pending', 'Pendding' => 'P',
            'A', 'P' => $status,
            default => throw new PMarketAPIException('status must be one of Active, Pending, A or P.'),
        };
    }

    private function assertTerminalUpdateRequest(TerminalUpdateRequest $request): void
    {
        $validationErrors = [];

        if (trim($request->name) === '') {
            $validationErrors[] = 'name:may not be empty';
        }

        if (mb_strlen($request->name) > 64) {
            $validationErrors[] = 'name:length must be between 0 and 64';
        }

        if ($request->resellerName === null || trim($request->resellerName) === '') {
            $validationErrors[] = 'resellerName:may not be empty';
        }

        if ($request->resellerName !== null && mb_strlen($request->resellerName) > 64) {
            $validationErrors[] = 'resellerName:length must be between 0 and 64';
        }

        if ($request->modelName === null || trim($request->modelName) === '') {
            $validationErrors[] = 'modelName:may not be empty';
        }

        if ($request->modelName !== null && mb_strlen($request->modelName) > 64) {
            $validationErrors[] = 'modelName:length must be between 0 and 64';
        }

        if ($request->tid !== null && $request->tid !== '') {
            $tidLength = mb_strlen($request->tid);
            if ($tidLength < 8 || $tidLength > 15) {
                $validationErrors[] = 'tid:length must be between 8 and 15';
            }
        }

        if ($request->serialNo !== null && $request->serialNo !== '' && mb_strlen($request->serialNo) > 32) {
            $validationErrors[] = 'serialNo:length must be between 0 and 32';
        }

        if ($request->merchantName !== null && mb_strlen($request->merchantName) > 64) {
            $validationErrors[] = 'merchantName:length must be between 0 and 64';
        }

        if ($request->location !== null && mb_strlen($request->location) > 32) {
            $validationErrors[] = 'location:length must be between 0 and 32';
        }

        if ($request->remark !== null && mb_strlen($request->remark) > 500) {
            $validationErrors[] = 'remark:length must be between 0 and 500';
        }

        if ($validationErrors !== []) {
            throw new PMarketAPIException(implode('; ', $validationErrors));
        }
    }

    /**
     * @return array<string, string>
     */
    private function terminalUpdatePayload(TerminalUpdateRequest $request): array
    {
        return array_filter([
            'name' => $request->name,
            'tid' => $request->tid,
            'serialNo' => $request->serialNo,
            'merchantName' => $request->merchantName,
            'resellerName' => $request->resellerName,
            'modelName' => $request->modelName,
            'location' => $request->location,
            'remark' => $request->remark,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function assertTerminalCopyRequest(TerminalCopyRequest $request, bool $bySerialNo): void
    {
        $validationErrors = [];

        if ($bySerialNo) {
            if ($request->sourceSerialNo === null || trim($request->sourceSerialNo) === '') {
                $validationErrors[] = 'sourceSerialNo:may not be empty';
            }
        } else {
            if ($request->terminalId === null || (string) $request->terminalId === '') {
                $validationErrors[] = 'terminalId:may not be empty';
            } else {
                try {
                    $this->assertPositiveInteger($request->terminalId, 'terminalId');
                } catch (PMarketAPIException $exception) {
                    $validationErrors[] = $exception->getMessage();
                }
            }
        }

        if (trim($request->name) === '') {
            $validationErrors[] = 'name:may not be empty';
        }

        if (mb_strlen($request->name) > 64) {
           $validationErrors[] = 'name:length must be between 0 and 64';
        }

        if ($request->tid !== null && $request->tid !== '') {
            $tidLength = mb_strlen($request->tid);
            if ($tidLength < 8 || $tidLength > 15) {
                $validationErrors[] = 'tid:length must be between 8 and 15';
            }
        }

        if ($request->serialNo === null || trim($request->serialNo) === '') {
            $validationErrors[] = 'serialNo:may not be empty';
        }

        if ($request->serialNo !== null && mb_strlen($request->serialNo) > 32) {
            $validationErrors[] = 'serialNo:length must be between 0 and 32';
        }

        if ($request->sourceSerialNo !== null && mb_strlen($request->sourceSerialNo) > 32) {
            $validationErrors[] = 'sourceSerialNo:length must be between 0 and 32';
        }

        if ($request->status === null || trim($request->status) === '') {
            $validationErrors[] = 'status:may not be empty';
        } else {
            try {
                $this->normalizeTerminalCreateStatus($request->status);
            } catch (PMarketAPIException $exception) {
                $validationErrors[] = $exception->getMessage();
            }
        }

        if ($validationErrors !== []) {
            throw new PMarketAPIException(implode('; ', $validationErrors));
        }
    }

    /**
     * @return array<string, string|int>
     */
    private function terminalCopyPayload(TerminalCopyRequest $request): array
    {
        return array_filter([
            'terminalId' => $request->terminalId !== null ? $this->assertPositiveInteger($request->terminalId, 'terminalId') : null,
            'sourceSerialNo' => $request->sourceSerialNo,
            'name' => $request->name,
            'tid' => $request->tid,
            'serialNo' => $request->serialNo,
            'status' => $request->status !== null ? $this->normalizeTerminalCreateStatus($request->status) : null,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function assertMoveTerminal(string $resellerName, string $merchantName): void
    {
        $validationErrors = [];

        if (trim($resellerName) === '') {
            $validationErrors[] = 'resellerName:may not be empty';
        }

        if (trim($merchantName) === '') {
            $validationErrors[] = 'merchantName:may not be empty';
        }

        if ($validationErrors !== []) {
            throw new PMarketAPIException(implode('; ', $validationErrors));
        }
    }

    private function normalizeTerminalStatus(string $status): string
    {
        return match ($status) {
            'Active' => 'A',
            'Inactive' => 'P',
            'Suspend' => 'S',
            'A', 'P', 'S' => $status,
            default => throw new PMarketAPIException('status must be one of Active, Inactive, Suspend, A, P or S.'),
        };
    }

    private function normalizeTerminalOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'Name' => 'name',
            'Tid' => 'tid',
            'SerialNo' => 'serialNo',
            'name', 'tid', 'serialNo' => $orderBy,
            default => throw new PMarketAPIException('orderBy must be one of Name, Tid, SerialNo, name, tid or serialNo.'),
        };
    }

    private function boolString(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
