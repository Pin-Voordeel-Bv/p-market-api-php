<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client;

use GuzzleHttp\ClientInterface;
use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\Terminal;
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

    private function boolString(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
