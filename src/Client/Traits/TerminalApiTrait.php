<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\Terminal;
use PinVandaag\PMarketAPI\Model\TerminalCopyRequest;
use PinVandaag\PMarketAPI\Model\TerminalCreateRequest;
use PinVandaag\PMarketAPI\Model\TerminalLog;
use PinVandaag\PMarketAPI\Model\TerminalLogDownloadTask;
use PinVandaag\PMarketAPI\Model\TerminalLogSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalNetwork;
use PinVandaag\PMarketAPI\Model\TerminalPed;
use PinVandaag\PMarketAPI\Model\TerminalSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalSystemUsage;
use PinVandaag\PMarketAPI\Model\TerminalUpdateRequest;
use Psr\Http\Message\ResponseInterface;

trait TerminalApiTrait
{
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

        $this->emptyResult(
            method: 'DELETE',
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

        $this->emptyResult(
            method: 'DELETE',
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
            endpoint: sprintf('/v1/3rdsys/terminals/%s/collect/log', rawurlencode((string) $terminalId)),
            actionDescription: sprintf('collect log for P Market terminal "%s"', $terminalId),
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
            actionDescription: sprintf('collect log for terminal "%s"', $serialNo),
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
            actionDescription: sprintf('download terminal log "%s"', $terminalLogId),
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
            actionDescription: sprintf('download terminal log "%s"', $terminalLogId),
            query: [
                'serialNo' => $serialNo,
            ],
        );
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

    private function normalizeTerminalCreateStatus(string $status): string
    {
        return match ($status) {
            'Active' => 'A',
            'Pending', 'Pendding' => 'P',
            'A', 'P' => $status,
            default => throw new PMarketAPIException('status must be one of Active, Pending, A or P.'),
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
}
