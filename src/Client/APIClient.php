<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\Terminal;

final class APIClient
{
    use LoggerAwareTrait;

    private readonly SerializerInterface $serializer;
    private ?string $apiKey = null;

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
            throw new PMarketAPIException('CCV API key cannot be empty.');
        }

        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Retrieve a terminal by TMS gateway and terminal ID.
     *
     * @throws PMarketAPIException
     */
    public function getTerminal(
        string $terminalId,
    ): Terminal {
        $terminalId = $this->assertStringField($terminalId, 'terminalId', required: true, maxLength: 50);

        /** @var Terminal $terminal */
        $terminal = $this->get(
            endpoint: sprintf('/v1/3rdsys/terminals/%s', rawurlencode($terminalId)),
            responseClass: Terminal::class,
            actionDescription: sprintf('retrieve P Market terminal "%s" on gateway "%s"', $terminalId, $tmsGateway),
        );

        return $terminal;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $responseClass
     * @param array<string, string> $headers
     *
     * @return T
     *
     * @throws PMarketAPIException
     */
    private function get(
        string $endpoint,
        string $responseClass,
        string $actionDescription,
        array $query = [],
        array $headers = [],
    ): object {
        $response = $this->request(
            method: 'GET',
            endpoint: $endpoint,
            options: [
                'headers' => $this->defaultHeaders() + $headers,
                // 'query' => $this->filterPayload($query),
                'query' => $query,
            ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeResponse($response, $responseClass, $actionDescription);
    }

    /**
     * @return array<string, string>
     */
    private function defaultHeaders(): array
    {
        if ($this->apiKey === null || $this->apiKey === '') {
            throw new PMarketAPIException('P Martket API key has not been configured.');
        }

        return [
            'Accept' => 'application/json',
            // @TODO: find how to handel apiKey and apiSecret in the Java source
            'X-Api-Key' => $this->apiKey,
        ];
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
    private function deserializeResponse(
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

        try {
            /** @var T $result */
            $result = $this->serializer->deserialize($body, $responseClass, 'json');
        } catch (SerializerException $exception) {
            throw new PMarketAPIException(
                sprintf('Could not deserialize CCV response for %s.', $actionDescription),
                0,
                $exception
            );
        }

        return $result;
    }

    private function assertStringField(
        mixed $value,
        string $fieldName,
        bool $required = false,
        ?int $minLength = null,
        ?int $maxLength = null,
    ): ?string {
        if ($value === null || $value === '') {
            if ($required) {
                throw new PMarketAPIException(sprintf('%s is required and must be a non-empty string.', $fieldName));
            }

            return null;
        }

        if (!is_string($value)) {
            throw new PMarketAPIException(sprintf('%s must be a string.', $fieldName));
        }

        $value = trim($value);

        if ($value === '') {
            if ($required) {
                throw new PMarketAPIException(sprintf('%s is required and must be a non-empty string.', $fieldName));
            }

            return null;
        }

        if ($minLength !== null && strlen($value) < $minLength) {
            throw new PMarketAPIException(sprintf('%s must be at least %d characters.', $fieldName, $minLength));
        }

        if ($maxLength !== null && strlen($value) > $maxLength) {
            throw new PMarketAPIException(sprintf('%s may not be longer than %d characters.', $fieldName, $maxLength));
        }

        return $value;
    }
}