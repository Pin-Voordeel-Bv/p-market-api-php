<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\Factory;
use PinVandaag\PMarketAPI\Model\FactoryModelSearchResult;
use Psr\Http\Message\ResponseInterface;

trait FactoryModelApiTrait
{
    public function searchFactoryModels(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $factoryName = null,
        ?string $modelName = null,
        ?string $productType = null,
    ): FactoryModelSearchResult {
        $this->assertPage($pageNo, $pageSize);

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeFactoryModelOrderBy($orderBy);
        }

        if ($factoryName !== null && $factoryName !== '') {
            $query['factoryName'] = $factoryName;
        }

        if ($modelName !== null && $modelName !== '') {
            $query['modelName'] = $modelName;
        }

        if ($productType !== null && $productType !== '') {
            $query['productType'] = $productType;
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/factory/models',
            query: $query,
            options: [
                'headers' => $this->defaultHeaders(),
            ],
            actionDescription: 'search P Market factory models',
        );

        return $this->deserializeFactoryModelSearchResult(
            $response,
            'search P Market factory models',
        );
    }

    private function deserializeFactoryModelSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): FactoryModelSearchResult {
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

        $factories = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $factoryData) {
            if (is_array($factoryData)) {
                $factories[] = $this->serializer->denormalize($factoryData, Factory::class);
            }
        }

        return new FactoryModelSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($factories)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($factories),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $factories,
        );
    }

    private function normalizeFactoryModelOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'name_desc', 'a.name DESC' => 'a.name DESC',
            'name_asc', 'a.name ASC' => 'a.name ASC',
            default => throw new PMarketAPIException('orderBy must be one of name_desc, name_asc, a.name DESC or a.name ASC.'),
        };
    }
}
