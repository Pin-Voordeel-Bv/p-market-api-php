<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client;

use GuzzleHttp\ClientInterface;
use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\Terminal;
use PinVandaag\PMarketAPI\Model\TerminalSearchResult;
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
