<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client;

use GuzzleHttp\ClientInterface;
use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\Terminal;
use PinVandaag\PMarketAPI\Model\TerminalCopyRequest;
use PinVandaag\PMarketAPI\Model\TerminalCreateRequest;
use PinVandaag\PMarketAPI\Model\TerminalSearchResult;
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
