<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\CreateTerminalGroupRkiRequest;
use PinVandaag\PMarketAPI\Model\TerminalGroupRki;
use PinVandaag\PMarketAPI\Model\TerminalGroupRkiSearchResult;
use Psr\Http\Message\ResponseInterface;

trait TerminalGroupRkiApiTrait
{
    public function searchGroupPushRkiTask(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        int|string|null $groupId = null,
        ?bool $pendingOnly = null,
        ?bool $historyOnly = null,
        ?string $keyWords = null,
    ): TerminalGroupRkiSearchResult {
        $this->assertPage($pageNo, $pageSize);

        if ($groupId === null || (string) $groupId === '') {
            throw new PMarketAPIException('terminalGroupId cannot be null and cannot be less than 1!');
        }

        $groupId = $this->assertPositiveInteger($groupId, 'groupId');

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
            'groupId' => (string) $groupId,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeRkiCreatedDateOrderBy($orderBy);
        }

        if ($pendingOnly !== null) {
            $query['pendingOnly'] = $pendingOnly ? 'true' : 'false';
        }

        if ($historyOnly !== null) {
            $query['historyOnly'] = $historyOnly ? 'true' : 'false';
        }

        if ($keyWords !== null && $keyWords !== '') {
            $query['keyWords'] = $keyWords;
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/terminalGroupRki',
            query: $query,
            options: ['headers' => $this->defaultHeaders()],
            actionDescription: 'search P Market terminal group RKI tasks',
        );

        return $this->deserializeTerminalGroupRkiSearchResult(
            $response,
            'search P Market terminal group RKI tasks',
        );
    }

    public function getGroupPushRkiTask(int|string $groupPushRkiTaskId): TerminalGroupRki
    {
        $groupPushRkiTaskId = $this->assertPositiveInteger($groupPushRkiTaskId, 'groupPushRkiTaskId');

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/terminalGroupRki/%s', rawurlencode((string) $groupPushRkiTaskId)),
            responseClass: TerminalGroupRki::class,
            actionDescription: sprintf('get P Market terminal group RKI task "%s"', $groupPushRkiTaskId),
        );
    }

    public function pushRkiKey2Group(CreateTerminalGroupRkiRequest $request): TerminalGroupRki
    {
        $this->assertCreateTerminalGroupRkiRequest($request);

        return $this->postResultData(
            endpoint: '/v1/3rdsys/terminalGroupRki',
            responseClass: TerminalGroupRki::class,
            actionDescription: 'push P Market RKI key to terminal group',
            body: $this->terminalGroupRkiPayload($request),
        );
    }

    public function disableGroupRkiPushTask(int|string $groupPushRkiTaskId): TerminalGroupRki
    {
        $groupPushRkiTaskId = $this->assertPositiveInteger($groupPushRkiTaskId, 'groupPushRkiTaskId');

        return $this->postResultData(
            endpoint: sprintf('/v1/3rdsys/terminalGroupRki/%s/suspend', rawurlencode((string) $groupPushRkiTaskId)),
            responseClass: TerminalGroupRki::class,
            actionDescription: sprintf('disable P Market terminal group RKI task "%s"', $groupPushRkiTaskId),
        );
    }

    public function deleteGroupRkiPushTask(int|string $groupPushRkiTaskId): bool
    {
        $groupPushRkiTaskId = $this->assertPositiveInteger($groupPushRkiTaskId, 'groupPushRkiTaskId');

        $this->emptyResult(
            method: 'GET',
            endpoint: sprintf('/v1/3rdsys/terminalGroupRki/%s', rawurlencode((string) $groupPushRkiTaskId)),
            actionDescription: sprintf('delete P Market terminal group RKI task "%s"', $groupPushRkiTaskId),
        );

        return true;
    }

    private function assertCreateTerminalGroupRkiRequest(CreateTerminalGroupRkiRequest $request): void
    {
        $errors = [];

        try {
            $this->assertPositiveInteger($request->groupId, 'groupId');
        } catch (PMarketAPIException $exception) {
            $errors[] = 'Terminal Group Id cannot be null and cannot be less than 1!';
        }

        if (trim($request->rkiKey) === '') {
            $errors[] = 'rkiKey:may not be empty';
        }

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function terminalGroupRkiPayload(CreateTerminalGroupRkiRequest $request): array
    {
        return array_filter([
            'groupId' => $this->assertPositiveInteger($request->groupId, 'groupId'),
            'rkiKey' => $request->rkiKey,
            'effectiveTime' => $request->effectiveTime,
            'expiredTime' => $request->expiredTime,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function deserializeTerminalGroupRkiSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): TerminalGroupRkiSearchResult {
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

        $tasks = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $taskData) {
            if (is_array($taskData)) {
                $tasks[] = $this->serializer->denormalize($taskData, TerminalGroupRki::class);
            }
        }

        return new TerminalGroupRkiSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($tasks)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($tasks),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $tasks,
        );
    }
}
