<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\DisablePushRkiTaskRequest;
use PinVandaag\PMarketAPI\Model\PushRki2TerminalRequest;
use PinVandaag\PMarketAPI\Model\PushRkiTask;
use PinVandaag\PMarketAPI\Model\PushRkiTaskSearchResult;
use Psr\Http\Message\ResponseInterface;

trait TerminalRkiApiTrait
{
    public function pushRkiKey2Terminal(PushRki2TerminalRequest $request): PushRkiTask
    {
        $this->assertPushRkiBasicRequest($request, 'pushRki2TerminalRequest');

        return $this->postResultData(
            endpoint: '/v1/3rdsys/terminalRkis',
            responseClass: PushRkiTask::class,
            actionDescription: 'push P Market RKI key to terminal',
            body: $this->pushRkiPayload($request),
        );
    }

    public function searchPushRkiTasks(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $terminalTid = null,
        ?string $rkiKey = null,
        ?string $status = null,
        ?string $serialNo = null,
    ): PushRkiTaskSearchResult {
        $this->assertPage($pageNo, $pageSize);

        if (trim((string) $terminalTid) === '' && trim((string) $serialNo) === '') {
            throw new PMarketAPIException('The property serialNo and tid in request cannot be blank at same time!');
        }

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeRkiCreatedDateOrderBy($orderBy);
        }

        if ($terminalTid !== null && $terminalTid !== '') {
            $query['terminalTid'] = $terminalTid;
        }

        if ($serialNo !== null && $serialNo !== '') {
            $query['serialNo'] = $serialNo;
        }

        if ($rkiKey !== null && $rkiKey !== '') {
            $query['rkiKey'] = $rkiKey;
        }

        if ($status !== null && $status !== '') {
            $query['status'] = $this->normalizeRkiPushStatus($status);
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/terminalRkis',
            query: $query,
            options: ['headers' => $this->defaultHeaders()],
            actionDescription: 'search P Market terminal RKI tasks',
        );

        return $this->deserializePushRkiTaskSearchResult($response, 'search P Market terminal RKI tasks');
    }

    public function getPushRkiTask(int|string $pushRkiTaskId): PushRkiTask
    {
        $pushRkiTaskId = $this->assertPositiveInteger($pushRkiTaskId, 'pushRkiTaskId');

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/terminalRkis/%s', rawurlencode((string) $pushRkiTaskId)),
            responseClass: PushRkiTask::class,
            actionDescription: sprintf('get P Market terminal RKI task "%s"', $pushRkiTaskId),
        );
    }

    public function disablePushRkiTask(DisablePushRkiTaskRequest $request): bool
    {
        $this->assertPushRkiBasicRequest($request, 'disablePushRkiTask');

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/terminalRkis/suspend',
            actionDescription: 'disable P Market terminal RKI push task',
            headers: ['Content-Type' => 'application/json'],
            body: $this->pushRkiPayload($request),
        );

        return true;
    }

    public function deleteTerminalRki(int|string $terminalRkiId): bool
    {
        $terminalRkiId = $this->assertPositiveInteger($terminalRkiId, 'terminalRkiId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rdsys/terminalRkis/%s', rawurlencode((string) $terminalRkiId)),
            actionDescription: sprintf('delete P Market terminal RKI "%s"', $terminalRkiId),
        );

        return true;
    }

    private function assertPushRkiBasicRequest(object $request, string $context): void
    {
        $errors = [];

        if (trim((string) ($request->tid ?? '')) === '' && trim((string) ($request->serialNo ?? '')) === '') {
            $errors[] = sprintf('The property serialNo and tid in %s cannot be blank at same time!', $context);
        }

        if (trim((string) ($request->rkiKey ?? '')) === '') {
            $errors[] = 'rkiKey:may not be empty';
        }

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function pushRkiPayload(object $request): array
    {
        return array_filter([
            'tid' => $request->tid ?? null,
            'serialNo' => $request->serialNo ?? null,
            'rkiKey' => $request->rkiKey ?? null,
            'effectiveTime' => $request->effectiveTime ?? null,
            'expiredTime' => $request->expiredTime ?? null,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function deserializePushRkiTaskSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): PushRkiTaskSearchResult {
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
                $tasks[] = $this->serializer->denormalize($taskData, PushRkiTask::class);
            }
        }

        return new PushRkiTaskSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($tasks)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($tasks),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $tasks,
        );
    }

    private function normalizeRkiCreatedDateOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'CreatedDate_desc', 'createdDate_desc', 'createdDate DESC', 'a.created_date DESC' => 'a.created_date DESC',
            'CreatedDate_asc', 'createdDate_asc', 'createdDate ASC', 'a.created_date ASC' => 'a.created_date ASC',
            default => throw new PMarketAPIException(
                'orderBy must be one of CreatedDate_desc, CreatedDate_asc, a.created_date DESC or a.created_date ASC.'
            ),
        };
    }

    private function normalizeRkiPushStatus(string $status): string
    {
        return match ($status) {
            'Active', 'active', 'A' => 'A',
            'Suspend', 'suspend', 'S' => 'S',
            'Completed', 'completed', 'C' => 'C',
            default => throw new PMarketAPIException('status must be one of Active, Suspend, Completed, A, S or C.'),
        };
    }
}
