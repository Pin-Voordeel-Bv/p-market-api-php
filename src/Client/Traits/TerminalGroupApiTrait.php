<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\TerminalGroup;
use PinVandaag\PMarketAPI\Model\TerminalGroupRequest;
use PinVandaag\PMarketAPI\Model\TerminalGroupSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalSearchResult;
use Psr\Http\Message\ResponseInterface;

trait TerminalGroupApiTrait
{
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

    public function searchTerminalsInGroup(
        int $pageNo,
        int $pageSize,
        int|string $groupId,
        ?string $orderBy = null,
        ?string $serialNo = null,
        ?string $merchantNames = null,
    ): TerminalSearchResult {
        $this->assertPage(
            $pageNo,
            $pageSize,
        );

        $groupId =
            $this->assertPositiveInteger(
                $groupId,
                'groupId',
            );

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
        ];

        foreach ([
            'orderBy' => $orderBy,
            'serialNo' => $serialNo,
            'merchantNames' => $merchantNames,
        ] as $key => $value) {
            if (
                $value !== null
                && $value !== ''
            ) {
                $query[$key] = $value;
            }
        }

        $response = $this->request(
            method: 'GET',
            endpoint: sprintf(
                '/v1/3rdsys/terminalGroups/%s/terminals',
                rawurlencode(
                    (string) $groupId,
                ),
            ),
            query: $query,
            options: [
                'headers' =>
                    $this->defaultHeaders(),
            ],
            actionDescription: sprintf(
                'search terminals in P Market terminal group "%s"',
                $groupId,
            ),
        );

        return $this->deserializeTerminalSearchResult(
            $response,
            sprintf(
                'search terminals in P Market terminal group "%s"',
                $groupId,
            ),
        );
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
}
