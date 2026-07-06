<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\DisablePushFirmwareTaskRequest;
use PinVandaag\PMarketAPI\Model\PushFirmwareTask;
use PinVandaag\PMarketAPI\Model\PushFirmwareTaskSearchResult;
use PinVandaag\PMarketAPI\Model\PushFirmwareToTerminalRequest;
use Psr\Http\Message\ResponseInterface;

trait TerminalFirmwareApiTrait
{
    public function pushFirmwareToTerminal(PushFirmwareToTerminalRequest $request): PushFirmwareTask
    {
        $this->assertPushFirmwareToTerminalRequest($request);

        return $this->postResultData(
            endpoint: '/v1/3rdsys/terminalFirmwares',
            responseClass: PushFirmwareTask::class,
            actionDescription: 'push P Market firmware to terminal',
            body: $this->pushFirmwareToTerminalPayload($request),
        );
    }

    public function searchPushFirmwareTasks(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $terminalTid = null,
        ?string $fmName = null,
        ?string $status = null,
        ?string $serialNo = null,
    ): PushFirmwareTaskSearchResult {
        $this->assertPage($pageNo, $pageSize);

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizePushFirmwareTaskOrderBy($orderBy);
        }

        if ($terminalTid !== null && $terminalTid !== '') {
            $query['terminalTid'] = $terminalTid;
        }

        if ($serialNo !== null && $serialNo !== '') {
            $query['serialNo'] = $serialNo;
        }

        if ($fmName !== null && $fmName !== '') {
            $query['fmName'] = $fmName;
        }

        if ($status !== null && $status !== '') {
            $query['status'] = $this->normalizePushFirmwareStatus($status);
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/terminalFirmwares',
            query: $query,
            options: [
                'headers' => $this->defaultHeaders(),
            ],
            actionDescription: 'search P Market push firmware tasks',
        );

        return $this->deserializePushFirmwareTaskSearchResult(
            $response,
            'search P Market push firmware tasks',
        );
    }

    public function getPushFirmwareTask(int|string $pushFirmwareTaskId): PushFirmwareTask
    {
        $pushFirmwareTaskId = $this->assertPositiveInteger($pushFirmwareTaskId, 'pushFirmwareTaskId');

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/terminalFirmwares/%s', rawurlencode((string) $pushFirmwareTaskId)),
            responseClass: PushFirmwareTask::class,
            actionDescription: sprintf('get P Market push firmware task "%s"', $pushFirmwareTaskId),
        );
    }

    public function disablePushFirmwareTask(DisablePushFirmwareTaskRequest $request): bool
    {
        $this->assertDisablePushFirmwareTaskRequest($request);

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/terminalFirmwares/suspend',
            actionDescription: 'disable P Market push firmware task',
            headers: ['Content-Type' => 'application/json'],
            body: $this->disablePushFirmwareTaskPayload($request),
        );

        return true;
    }

    public function deleteTerminalFirmware(int|string $terminalFirmwareId): bool
    {
        $terminalFirmwareId = $this->assertPositiveInteger($terminalFirmwareId, 'terminalFirmwareId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rdsys/terminalFirmwares/%s', rawurlencode((string) $terminalFirmwareId)),
            actionDescription: sprintf('delete P Market terminal firmware "%s"', $terminalFirmwareId),
        );

        return true;
    }

    private function assertPushFirmwareToTerminalRequest(PushFirmwareToTerminalRequest $request): void
    {
        $errors = [];

        if (trim((string) $request->tid) === '' && trim((string) $request->serialNo) === '') {
            $errors[] = 'The property serialNo and tid in pushFirmwareToTerminalRequest cannot be blank at same time!';
        }

        if (trim($request->fmName) === '') {
            $errors[] = 'fmName:may not be empty';
        }

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function assertDisablePushFirmwareTaskRequest(DisablePushFirmwareTaskRequest $request): void
    {
        $errors = [];

        if (trim((string) $request->tid) === '' && trim((string) $request->serialNo) === '') {
            $errors[] = 'The property serialNo and tid in disablePushFirmwareTask cannot be blank at same time!';
        }

        if (trim($request->fmName) === '') {
            $errors[] = 'fmName:may not be empty';
        }

        if ($errors !== []) {
            throw new PMarketAPIException(implode('; ', $errors));
        }
    }

    private function pushFirmwareToTerminalPayload(PushFirmwareToTerminalRequest $request): array
    {
        return array_filter([
            'tid' => $request->tid,
            'serialNo' => $request->serialNo,
            'fmName' => $request->fmName,
            'wifiOnly' => $request->wifiOnly,
            'effectiveTime' => $request->effectiveTime,
            'expiredTime' => $request->expiredTime,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function disablePushFirmwareTaskPayload(DisablePushFirmwareTaskRequest $request): array
    {
        return array_filter([
            'tid' => $request->tid,
            'serialNo' => $request->serialNo,
            'fmName' => $request->fmName,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function deserializePushFirmwareTaskSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): PushFirmwareTaskSearchResult {
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
                $tasks[] = $this->serializer->denormalize($taskData, PushFirmwareTask::class);
            }
        }

        return new PushFirmwareTaskSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? count($tasks)),
            totalCount: isset($pageInfo['totalCount']) ? (int) $pageInfo['totalCount'] : count($tasks),
            hasNext: (bool) ($pageInfo['hasNext'] ?? false),
            dataSet: $tasks,
        );
    }

    private function normalizePushFirmwareTaskOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'CreatedDate_desc', 'createdDate_desc', 'createdDate DESC' => 'createdDate DESC',
            'CreatedDate_asc', 'createdDate_asc', 'createdDate ASC' => 'createdDate ASC',
            default => throw new PMarketAPIException('orderBy must be one of CreatedDate_desc, CreatedDate_asc, createdDate DESC or createdDate ASC.'),
        };
    }

    private function normalizePushFirmwareStatus(string $status): string
    {
        return match ($status) {
            'Active' => 'A',
            'Suspend' => 'S',
            'Completed' => 'C',
            'A', 'S', 'C' => $status,
            default => throw new PMarketAPIException('status must be one of Active, Suspend, Completed, A, S or C.'),
        };
    }
}
