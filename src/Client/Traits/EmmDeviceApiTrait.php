<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\EmmDevice;
use PinVandaag\PMarketAPI\Model\EmmDeviceBatchDeleteRequest;
use PinVandaag\PMarketAPI\Model\EmmDeviceBatchMoveRequest;
use PinVandaag\PMarketAPI\Model\EmmDeviceDetail;
use PinVandaag\PMarketAPI\Model\EmmDeviceLostModeRequest;
use PinVandaag\PMarketAPI\Model\EmmDeviceRegisterQRCodeCreate;
use PinVandaag\PMarketAPI\Model\EmmDeviceRegisterQRCodeCreateRequest;
use PinVandaag\PMarketAPI\Model\EmmDeviceResetPasswordRequest;
use PinVandaag\PMarketAPI\Model\EmmDeviceSearchResult;
use PinVandaag\PMarketAPI\Model\EmmDeviceUpdateRequest;
use PinVandaag\PMarketAPI\Model\EmmZteQuickUploadRecordCreateRequest;
use Psr\Http\Message\ResponseInterface;

trait EmmDeviceApiTrait
{
    public function createRegisterQRCode(EmmDeviceRegisterQRCodeCreateRequest $request): EmmDeviceRegisterQRCodeCreate
    {
        $this->assertEmmDeviceRegisterQRCodeCreateRequest($request);

        return $this->postResultData(
            endpoint: '/v1/3rdsys/emm/devices/register-qrcode',
            responseClass: EmmDeviceRegisterQRCodeCreate::class,
            actionDescription: 'create P Market EMM device register QR code',
            body: [
                'resellerName' => $request->resellerName,
                'merchantName' => $request->merchantName,
                'type' => $this->normalizeEmmDeviceType($request->type),
                'expireDate' => $request->expireDate,
            ],
        );
    }

    public function searchEmmDevice(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $name = null,
        ?string $serialNo = null,
        ?string $mfrName = null,
        ?string $modelName = null,
        ?string $resellerName = null,
        ?string $merchantName = null,
        ?string $status = null,
        ?string $iccId = null,
        ?string $imei = null,
    ): EmmDeviceSearchResult {
        $this->assertPage($pageNo, $pageSize);

        if (($mfrName !== null && $mfrName !== '') xor ($modelName !== null && $modelName !== '')) {
            throw new PMarketAPIException('Both the manufacturer name and the model name must exist');
        }

        $query = [
            'pageNo' => (string) $pageNo,
            'limit' => (string) $pageSize,
        ];

        if ($orderBy !== null && $orderBy !== '') {
            $query['orderBy'] = $this->normalizeEmmDeviceOrderBy($orderBy);
        }

        foreach ([
            'name' => $name,
            'serialNo' => $serialNo,
            'mfrName' => $mfrName,
            'modelName' => $modelName,
            'resellerName' => $resellerName,
            'merchantName' => $merchantName,
            'iccId' => $iccId,
            'imei' => $imei,
        ] as $key => $value) {
            if ($value !== null && $value !== '') {
                $query[$key] = $value;
            }
        }

        if ($status !== null && $status !== '') {
            $query['status'] = $this->normalizeEmmDeviceStatus($status);
        }

        $response = $this->request(
            method: 'GET',
            endpoint: '/v1/3rdsys/emm/devices',
            query: $query,
            options: ['headers' => $this->defaultHeaders()],
            actionDescription: 'search P Market EMM devices',
        );

        return $this->deserializeEmmDeviceSearchResult($response, 'search P Market EMM devices');
    }

    public function getEmmDevice(int|string $deviceId): EmmDeviceDetail
    {
        $deviceId = $this->assertPositiveInteger($deviceId, 'deviceId');

        return $this->getResultData(
            endpoint: sprintf('/v1/3rdsys/emm/devices/%s', rawurlencode((string) $deviceId)),
            responseClass: EmmDeviceDetail::class,
            actionDescription: sprintf('get P Market EMM device "%s"', $deviceId),
        );
    }

    public function updateEmmDevice(int|string $deviceId, EmmDeviceUpdateRequest $request): bool
    {
        $deviceId = $this->assertPositiveInteger($deviceId, 'deviceId');
        $this->assertEmmDeviceUpdateRequest($request);

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/emm/devices/%s', rawurlencode((string) $deviceId)),
            actionDescription: sprintf('update P Market EMM device "%s"', $deviceId),
            headers: ['Content-Type' => 'application/json'],
            body: [
                'deviceName' => $request->deviceName,
                'resellerName' => $request->resellerName,
                'merchantName' => $request->merchantName,
            ],
        );

        return true;
    }

    public function batchMoveEmmDevice(EmmDeviceBatchMoveRequest $request): bool
    {
        $this->assertEmmDeviceBatchMoveRequest($request);

        $this->emptyResult(
            method: 'PUT',
            endpoint: '/v1/3rdsys/emm/devices/batch/move',
            actionDescription: 'batch move P Market EMM devices',
            headers: ['Content-Type' => 'application/json'],
            body: [
                'deviceIds' => array_values($request->deviceIds),
                'resellerName' => $request->resellerName,
                'merchantName' => $request->merchantName,
            ],
        );

        return true;
    }

    public function deleteEmmDevice(int|string $deviceId): bool
    {
        $deviceId = $this->assertPositiveInteger($deviceId, 'deviceId');

        $this->emptyResult(
            method: 'DELETE',
            endpoint: sprintf('/v1/3rdsys/emm/devices/%s', rawurlencode((string) $deviceId)),
            actionDescription: sprintf('delete P Market EMM device "%s"', $deviceId),
        );

        return true;
    }

    public function batchDeleteEmmDevice(EmmDeviceBatchDeleteRequest $request): bool
    {
        if ($request->deviceIds === []) {
            throw new PMarketAPIException('Parameter deviceIds cannot be null!');
        }

        $this->emptyResult(
            method: 'DELETE',
            endpoint: '/v1/3rdsys/emm/devices/batch/delete',
            actionDescription: 'batch delete P Market EMM devices',
            headers: ['Content-Type' => 'application/json'],
            body: ['deviceIds' => array_values($request->deviceIds)],
        );

        return true;
    }

    public function rebootEmmDevice(int|string $deviceId): bool
    {
        return $this->simpleEmmDeviceAction($deviceId, 'reboot', 'reboot P Market EMM device');
    }

    public function lockEmmDeviceScreen(int|string $deviceId): bool
    {
        return $this->simpleEmmDeviceAction($deviceId, 'lockscreen', 'lock P Market EMM device screen');
    }

    public function resumeEmmDevice(int|string $deviceId): bool
    {
        return $this->simpleEmmDeviceAction($deviceId, 'resume', 'resume P Market EMM device', 'POST');
    }

    public function disableEmmDevice(int|string $deviceId): bool
    {
        return $this->simpleEmmDeviceAction($deviceId, 'disable', 'disable P Market EMM device', 'POST');
    }

    public function syncDeviceDetail(int|string $deviceId): bool
    {
        return $this->simpleEmmDeviceAction($deviceId, 'sync', 'sync P Market EMM device detail', 'POST');
    }

    public function stopEmmDeviceLostMode(int|string $deviceId): bool
    {
        return $this->simpleEmmDeviceAction($deviceId, 'stoplost', 'stop P Market EMM device lost mode');
    }

    public function resetEmmDevicePassword(
        int|string $deviceId,
        EmmDeviceResetPasswordRequest $request,
    ): bool {
        $deviceId = $this->assertPositiveInteger($deviceId, 'deviceId');

        if (trim($request->password) === '') {
            throw new PMarketAPIException('Parameter password cannot be null!');
        }

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/emm/devices/%s/resetpw', rawurlencode((string) $deviceId)),
            actionDescription: sprintf('reset P Market EMM device password "%s"', $deviceId),
            headers: ['Content-Type' => 'application/json'],
            body: [
                'password' => $request->password,
                'lockNow' => $request->lockNow,
            ],
        );

        return true;
    }

    public function startEmmDeviceLostMode(
        int|string $deviceId,
        EmmDeviceLostModeRequest $request,
    ): bool {
        $deviceId = $this->assertPositiveInteger($deviceId, 'deviceId');

        if (trim($request->lostMessage) === '') {
            throw new PMarketAPIException('Parameter lostMessage cannot be null!');
        }

        if (mb_strlen($request->lostMessage) > 64) {
            throw new PMarketAPIException('Parameter lostMessage is too long, maxlength is 64!');
        }

        if (trim($request->lostPhoneNumber) === '') {
            throw new PMarketAPIException('Parameter lostPhoneNumber cannot be null!');
        }

        if (mb_strlen($request->lostPhoneNumber) > 32) {
            throw new PMarketAPIException('Parameter lostPhoneNumber is too long, maxlength is 32!');
        }

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/emm/devices/%s/startlost', rawurlencode((string) $deviceId)),
            actionDescription: sprintf('start P Market EMM device lost mode "%s"', $deviceId),
            headers: ['Content-Type' => 'application/json'],
            body: [
                'lostMessage' => $request->lostMessage,
                'lostPhoneNumber' => $request->lostPhoneNumber,
            ],
        );

        return true;
    }

    public function clearEmmAppData(int|string $deviceId, string $installedAppIds): bool
    {
        $deviceId = $this->assertPositiveInteger($deviceId, 'deviceId');

        if (trim($installedAppIds) === '') {
            throw new PMarketAPIException('Parameter installedAppIds cannot be null !');
        }

        $this->emptyResult(
            method: 'PUT',
            endpoint: sprintf('/v1/3rdsys/emm/devices/%s/app/clear', rawurlencode((string) $deviceId)),
            actionDescription: sprintf('clear P Market EMM app data "%s"', $deviceId),
            query: ['installedAppIds' => $installedAppIds],
            headers: ['Content-Type' => 'application/json'],
        );

        return true;
    }

    public function submitEmmZteQuickUploadRecord(EmmZteQuickUploadRecordCreateRequest $request): bool
    {
        $this->assertEmmZteQuickUploadRecordCreateRequest($request);

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/emm/devices/zte/quick-upload',
            actionDescription: 'submit P Market EMM ZTE quick upload record',
            headers: ['Content-Type' => 'application/json'],
            body: array_filter([
                'resellerName' => $request->resellerName,
                'merchantName' => $request->merchantName,
                'identifierType' => $this->normalizeEmmZteIdentifierType($request->identifierType),
                'manufacturer' => $request->manufacturer,
                'model' => $request->model,
                'numbers' => $request->numbers,
            ], static fn ($value): bool => $value !== null && $value !== ''),
        );

        return true;
    }

    private function simpleEmmDeviceAction(
        int|string $deviceId,
        string $action,
        string $description,
        string $method = 'PUT',
    ): bool {
        $deviceId = $this->assertPositiveInteger($deviceId, 'deviceId');

        $this->emptyResult(
            method: $method,
            endpoint: sprintf('/v1/3rdsys/emm/devices/%s/%s', rawurlencode((string) $deviceId), $action),
            actionDescription: sprintf('%s "%s"', $description, $deviceId),
            headers: ['Content-Type' => 'application/json'],
        );

        return true;
    }

    private function deserializeEmmDeviceSearchResult(
        ResponseInterface $response,
        string $actionDescription,
    ): EmmDeviceSearchResult {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new PMarketAPIException($this->errorMessageFromResponseBody($body, $actionDescription, $statusCode), $statusCode);
        }

        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            throw new PMarketAPIException(sprintf('Could not decode P Market response for %s.', $actionDescription));
        }

        if (($decoded['businessCode'] ?? null) !== 0) {
            throw new PMarketAPIException($this->resultErrorMessage($decoded, $actionDescription, $statusCode), (int) ($decoded['businessCode'] ?? 0));
        }

        $pageInfo = $decoded['pageInfo'] ?? $decoded;
        $dataSet = $pageInfo['dataSet'] ?? $pageInfo['dataset'] ?? $decoded['dataset'] ?? [];

        $rows = [];
        foreach (is_array($dataSet) ? $dataSet : [] as $rowData) {
            if (is_array($rowData)) {
                $rows[] = $this->serializer->denormalize($rowData, EmmDevice::class);
            }
        }

        return new EmmDeviceSearchResult(
            pageNo: (int) ($pageInfo['pageNo'] ?? $decoded['pageNo'] ?? 1),
            limit: (int) ($pageInfo['limit'] ?? $decoded['limit'] ?? count($rows)),
            totalCount: isset($pageInfo['totalCount'])
                ? (int) $pageInfo['totalCount']
                : (int) ($decoded['totalCount'] ?? count($rows)),
            hasNext: (bool) ($pageInfo['hasNext'] ?? $decoded['hasNext'] ?? false),
            dataSet: $rows,
        );
    }

    private function assertEmmDeviceRegisterQRCodeCreateRequest(
        EmmDeviceRegisterQRCodeCreateRequest $request,
    ): void {
        $this->assertEmmRequiredString($request->resellerName, 'resellerName', 64);
        $this->assertEmmRequiredString($request->merchantName, 'merchantName', 128);

        if (trim($request->type) === '') {
            throw new PMarketAPIException('Parameter type cannot be null!');
        }

        if ((string) $request->expireDate === '') {
            throw new PMarketAPIException('Parameter expireDate cannot be null!');
        }
    }

    private function assertEmmDeviceUpdateRequest(EmmDeviceUpdateRequest $request): void
    {
        $this->assertEmmRequiredString($request->deviceName, 'deviceName', 64);
        $this->assertEmmRequiredString($request->resellerName, 'resellerName', 64);
        $this->assertEmmRequiredString($request->merchantName, 'merchantName', 128);
    }

    private function assertEmmDeviceBatchMoveRequest(EmmDeviceBatchMoveRequest $request): void
    {
        if ($request->deviceIds === []) {
            throw new PMarketAPIException('Parameter deviceIds cannot be null!');
        }

        $this->assertEmmRequiredString($request->resellerName, 'resellerName', 64);
        $this->assertEmmRequiredString($request->merchantName, 'merchantName', 128);
    }

    private function assertEmmZteQuickUploadRecordCreateRequest(
        EmmZteQuickUploadRecordCreateRequest $request,
    ): void {
        $this->assertEmmRequiredString($request->resellerName, 'resellerName', 64);
        $this->assertEmmRequiredString($request->merchantName, 'merchantName', 128);

        if (trim($request->identifierType) === '') {
            throw new PMarketAPIException('Parameter identifierType cannot be null!');
        }

        if (trim($request->numbers) === '') {
            throw new PMarketAPIException('Parameter numbers cannot be null!');
        }

        if ($this->normalizeEmmZteIdentifierType($request->identifierType) === 'S') {
            if (trim((string) $request->manufacturer) === '' || trim((string) $request->model) === '') {
                throw new PMarketAPIException('Both the manufacturer name and the model name must exist');
            }
        }
    }

    private function assertEmmRequiredString(string $value, string $field, int $maxLength): void
    {
        if (trim($value) === '') {
            throw new PMarketAPIException(sprintf('Parameter %s cannot be null!', $field));
        }

        if (mb_strlen($value) > $maxLength) {
            throw new PMarketAPIException(sprintf('Parameter %s is too long, maxlength is %d!', $field, $maxLength));
        }
    }

    private function normalizeEmmDeviceOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'EmmDeviceSN_desc', 'emmDeviceSN_desc', 'serialNo DESC' => 'serialNo DESC',
            'EmmDeviceSN_asc', 'emmDeviceSN_asc', 'serialNo ASC' => 'serialNo ASC',
            'EmmDeviceRegisterTime_desc', 'emmDeviceRegisterTime_desc', 'registerTime DESC' => 'registerTime DESC',
            'EmmDeviceRegisterTime_asc', 'emmDeviceRegisterTime_asc', 'registerTime ASC' => 'registerTime ASC',
            default => throw new PMarketAPIException(
                'orderBy must be one of EmmDeviceSN_desc, EmmDeviceSN_asc, EmmDeviceRegisterTime_desc or EmmDeviceRegisterTime_asc.'
            ),
        };
    }

    private function normalizeEmmDeviceStatus(string $status): string
    {
        return match ($status) {
            'UN_CERTIFICATED', 'Uncertificated', 'uncertificated', 'U' => 'U',
            'ACTIVE', 'Active', 'active', 'A' => 'A',
            'LOST', 'Lost', 'lost', 'L' => 'L',
            default => throw new PMarketAPIException('status must be one of UN_CERTIFICATED, ACTIVE, LOST, U, A or L.'),
        };
    }

    private function normalizeEmmDeviceType(string $type): string
    {
        return match ($type) {
            'COMPANY_OWNER', 'CompanyOwner', 'company_owner', 'C' => 'C',
            default => throw new PMarketAPIException('type must be COMPANY_OWNER or C.'),
        };
    }

    private function normalizeEmmZteIdentifierType(string $identifierType): string
    {
        return match ($identifierType) {
            'IMEI', 'imei', 'I' => 'I',
            'SERIAL_NUMBER', 'SerialNumber', 'serial_number', 'S' => 'S',
            default => throw new PMarketAPIException('identifierType must be IMEI, SERIAL_NUMBER, I or S.'),
        };
    }
}
