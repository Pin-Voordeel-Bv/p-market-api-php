<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI;

use DateTimeInterface;
use GuzzleHttp\Client;
use PinVandaag\PMarketAPI\Client\APIClient;
use PinVandaag\PMarketAPI\Model\ApkParameter;
use PinVandaag\PMarketAPI\Model\ApkParameterSearchResult;
use PinVandaag\PMarketAPI\Model\ApkParamPid;
use PinVandaag\PMarketAPI\Model\AppCost;
use PinVandaag\PMarketAPI\Model\AppSearchResult;
use PinVandaag\PMarketAPI\Model\CreateApkParameterRequest;
use PinVandaag\PMarketAPI\Model\CreateTerminalApkPartialParamRequest;
use PinVandaag\PMarketAPI\Model\CreateTerminalApkRequest;
use PinVandaag\PMarketAPI\Model\CreateTerminalGroupApkPartialParamRequest;
use PinVandaag\PMarketAPI\Model\CreateTerminalGroupApkRequest;
use PinVandaag\PMarketAPI\Model\CreateTerminalGroupRkiRequest;
use PinVandaag\PMarketAPI\Model\DeviceEmmPolicyCreateRequest;
use PinVandaag\PMarketAPI\Model\DisablePushFirmwareTaskRequest;
use PinVandaag\PMarketAPI\Model\DisablePushRkiTaskRequest;
use PinVandaag\PMarketAPI\Model\EmmApp;
use PinVandaag\PMarketAPI\Model\EmmAppAvailableTestVersionList;
use PinVandaag\PMarketAPI\Model\EmmAppCreateRequest;
use PinVandaag\PMarketAPI\Model\EmmAppDetail;
use PinVandaag\PMarketAPI\Model\EmmAppPermission;
use PinVandaag\PMarketAPI\Model\EmmAppSearchResult;
use PinVandaag\PMarketAPI\Model\EmmDeviceBatchDeleteRequest;
use PinVandaag\PMarketAPI\Model\EmmDeviceBatchMoveRequest;
use PinVandaag\PMarketAPI\Model\EmmDeviceDetail;
use PinVandaag\PMarketAPI\Model\EmmDeviceLostModeRequest;
use PinVandaag\PMarketAPI\Model\EmmDeviceRegisterQRCodeCreate;
use PinVandaag\PMarketAPI\Model\EmmDeviceRegisterQRCodeCreateRequest;
use PinVandaag\PMarketAPI\Model\EmmDeviceResetPasswordRequest;
use PinVandaag\PMarketAPI\Model\EmmDeviceSearchResult;
use PinVandaag\PMarketAPI\Model\EmmDeviceUpdateRequest;
use PinVandaag\PMarketAPI\Model\EmmDeviceDashboardDetailSearchResult;
use PinVandaag\PMarketAPI\Model\EmmDeviceDashboardMonitor;
use PinVandaag\PMarketAPI\Model\EmmDeviceInstalledAppSearchResult;
use PinVandaag\PMarketAPI\Model\EmmDeviceLocation;
use PinVandaag\PMarketAPI\Model\EmmPolicy;
use PinVandaag\PMarketAPI\Model\EmmZteQuickUploadRecordCreateRequest;
use PinVandaag\PMarketAPI\Model\EntityAttribute;
use PinVandaag\PMarketAPI\Model\EntityAttributeCreateRequest;
use PinVandaag\PMarketAPI\Model\EntityAttributeLabelUpdateRequest;
use PinVandaag\PMarketAPI\Model\EntityAttributeSearchResult;
use PinVandaag\PMarketAPI\Model\EntityAttributeUpdateRequest;
use PinVandaag\PMarketAPI\Model\FactoryModelSearchResult;
use PinVandaag\PMarketAPI\Model\GoInsightDataQueryResult;
use PinVandaag\PMarketAPI\Model\Merchant;
use PinVandaag\PMarketAPI\Model\MerchantCategory;
use PinVandaag\PMarketAPI\Model\MerchantCategoryRequest;
use PinVandaag\PMarketAPI\Model\MerchantCreateRequest;
use PinVandaag\PMarketAPI\Model\MerchantEmmPolicyCreateRequest;
use PinVandaag\PMarketAPI\Model\MerchantSearchResult;
use PinVandaag\PMarketAPI\Model\MerchantUpdateRequest;
use PinVandaag\PMarketAPI\Model\MerchantVariableCreateRequest;
use PinVandaag\PMarketAPI\Model\MerchantVariableUpdateRequest;
use PinVandaag\PMarketAPI\Model\OptimizedParameterPushHistorySearchResult;
use PinVandaag\PMarketAPI\Model\ParameterPushHistorySearchResult;
use PinVandaag\PMarketAPI\Model\ParameterVariable;
use PinVandaag\PMarketAPI\Model\ParameterVariableDeleteRequest;
use PinVandaag\PMarketAPI\Model\ParameterVariableSearchResult;
use PinVandaag\PMarketAPI\Model\PushFirmwareTask;
use PinVandaag\PMarketAPI\Model\PushFirmwareTaskSearchResult;
use PinVandaag\PMarketAPI\Model\PushFirmwareToTerminalRequest;
use PinVandaag\PMarketAPI\Model\PushRki2TerminalRequest;
use PinVandaag\PMarketAPI\Model\PushRkiTask;
use PinVandaag\PMarketAPI\Model\PushRkiTaskSearchResult;
use PinVandaag\PMarketAPI\Model\Reseller;
use PinVandaag\PMarketAPI\Model\ResellerCreateRequest;
use PinVandaag\PMarketAPI\Model\ResellerEmmPolicyCreateRequest;
use PinVandaag\PMarketAPI\Model\ResellerRkiKeySearchResult;
use PinVandaag\PMarketAPI\Model\ResellerSearchResult;
use PinVandaag\PMarketAPI\Model\ResellerUpdateRequest;
use PinVandaag\PMarketAPI\Model\SubscribeEmmAppSearchResult;
use PinVandaag\PMarketAPI\Model\Terminal;
use PinVandaag\PMarketAPI\Model\TerminalApk;
use PinVandaag\PMarketAPI\Model\TerminalApkSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalCopyRequest;
use PinVandaag\PMarketAPI\Model\TerminalCreateRequest;
use PinVandaag\PMarketAPI\Model\TerminalGeoFenceWhiteListRequest;
use PinVandaag\PMarketAPI\Model\TerminalGeoFenceWhiteListSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalGroup;
use PinVandaag\PMarketAPI\Model\TerminalGroupApk;
use PinVandaag\PMarketAPI\Model\TerminalGroupApkSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalGroupRequest;
use PinVandaag\PMarketAPI\Model\TerminalGroupRki;
use PinVandaag\PMarketAPI\Model\TerminalGroupRkiSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalGroupSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalLogDownloadTask;
use PinVandaag\PMarketAPI\Model\TerminalLogSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalNetwork;
use PinVandaag\PMarketAPI\Model\TerminalParameterVariableRequest;
use PinVandaag\PMarketAPI\Model\TerminalPed;
use PinVandaag\PMarketAPI\Model\TerminalSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalSystemUsage;
use PinVandaag\PMarketAPI\Model\TerminalUpdateRequest;
use PinVandaag\PMarketAPI\Model\UpdateApkParameterRequest;
use PinVandaag\PMarketAPI\Model\UpdateTerminalApkRequest;
use Psr\Log\LoggerInterface;
use SensitiveParameter;

final class PMarketAPIClient
{
    private APIClient $apiClient;

    public function __construct(
        ?APIClient $apiClient = null,
        ?LoggerInterface $logger = null,
        ?string $baseUri = null,
    ) {
        $this->apiClient = $apiClient ?? new APIClient(new Client(), $baseUri ?? '');

        if ($logger !== null) {
            $this->apiClient->setLogger($logger);
        }
    }

    public function configure(
        string $baseUri,
        #[SensitiveParameter] string $apiKey,
        #[SensitiveParameter] string $apiSecret,
    ): self {
        $this->apiClient
            ->setBaseUri($baseUri)
            ->setApiKey($apiKey)
            ->setApiSecret($apiSecret);

        return $this;
    }

    public function searchApp(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $name = null,
        ?string $osType = null,
        ?string $chargeType = null,
        ?string $baseType = null,
        ?string $appStatus = null,
        ?string $apkStatus = null,
        ?bool $specificReseller = null,
        ?bool $specificMerchantCategory = null,
        ?bool $includeSubscribedApp = null,
        ?string $resellerName = null,
        ?string $modelName = null,
    ): AppSearchResult {
        return $this->apiClient->searchApp(
            $pageNo,
            $pageSize,
            $orderBy,
            $name,
            $osType,
            $chargeType,
            $baseType,
            $appStatus,
            $apkStatus,
            $specificReseller,
            $specificMerchantCategory,
            $includeSubscribedApp,
            $resellerName,
            $modelName,
        );
    }

    public function getAppCost(int|string $resellerId, int|string $appId): AppCost
    {
        return $this->apiClient->getAppCost($resellerId, $appId);
    }

    public function searchApkParamPidList(
        string $paramTemplateName,
        string $packageName,
        string $versionName,
    ): ApkParamPid {
        return $this->apiClient->searchApkParamPidList($paramTemplateName, $packageName, $versionName);
    }

    public function searchEmmApp(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        string $resellerName = '',
        ?string $keyWords = null,
        ?string $type = null,
    ): EmmAppSearchResult {
        return $this->apiClient->searchEmmApp(
            $pageNo,
            $pageSize,
            $orderBy,
            $resellerName,
            $keyWords,
            $type,
        );
    }

    public function createEmmApp(EmmAppCreateRequest $request): EmmApp
    {
        return $this->apiClient->createEmmApp($request);
    }

    public function getEmmAppDetail(int|string $appId): EmmAppDetail
    {
        return $this->apiClient->getEmmAppDetail($appId);
    }

    public function removeEmmApp(int|string $appId, string $resellerName): bool
    {
        return $this->apiClient->removeEmmApp($appId, $resellerName);
    }

    public function searchSubscribeEmmApp(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $name = null,
        ?bool $isSubscribed = null,
    ): SubscribeEmmAppSearchResult {
        return $this->apiClient->searchSubscribeEmmApp($pageNo, $pageSize, $name, $isSubscribed);
    }

    public function subscribeEmmApp(int|string $appId): bool
    {
        return $this->apiClient->subscribeEmmApp($appId);
    }

    public function unSubscribeEmmApp(int|string $appId): bool
    {
        return $this->apiClient->unSubscribeEmmApp($appId);
    }

    public function getEmmAppPermissionList(int|string $appId): EmmAppPermission
    {
        return $this->apiClient->getEmmAppPermissionList($appId);
    }

    public function getAvailableTestTrackVersionList(int|string $appId): EmmAppAvailableTestVersionList
    {
        return $this->apiClient->getAvailableTestTrackVersionList($appId);
    }

    public function createRegisterQRCode(EmmDeviceRegisterQRCodeCreateRequest $request): EmmDeviceRegisterQRCodeCreate
    {
        return $this->apiClient->createRegisterQRCode($request);
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
        return $this->apiClient->searchEmmDevice(
            $pageNo,
            $pageSize,
            $orderBy,
            $name,
            $serialNo,
            $mfrName,
            $modelName,
            $resellerName,
            $merchantName,
            $status,
            $iccId,
            $imei,
        );
    }

    public function getEmmDevice(int|string $deviceId): EmmDeviceDetail
    {
        return $this->apiClient->getEmmDevice($deviceId);
    }

    public function updateEmmDevice(int|string $deviceId, EmmDeviceUpdateRequest $request): bool
    {
        return $this->apiClient->updateEmmDevice($deviceId, $request);
    }

    public function batchMoveEmmDevice(EmmDeviceBatchMoveRequest $request): bool
    {
        return $this->apiClient->batchMoveEmmDevice($request);
    }

    public function deleteEmmDevice(int|string $deviceId): bool
    {
        return $this->apiClient->deleteEmmDevice($deviceId);
    }

    public function batchDeleteEmmDevice(EmmDeviceBatchDeleteRequest $request): bool
    {
        return $this->apiClient->batchDeleteEmmDevice($request);
    }

    public function rebootEmmDevice(int|string $deviceId): bool
    {
        return $this->apiClient->rebootEmmDevice($deviceId);
    }

    public function lockEmmDeviceScreen(int|string $deviceId): bool
    {
        return $this->apiClient->lockEmmDeviceScreen($deviceId);
    }

    public function resetEmmDevicePassword(
        int|string $deviceId,
        EmmDeviceResetPasswordRequest $request,
    ): bool {
        return $this->apiClient->resetEmmDevicePassword($deviceId, $request);
    }

    public function startEmmDeviceLostMode(
        int|string $deviceId,
        EmmDeviceLostModeRequest $request,
    ): bool {
        return $this->apiClient->startEmmDeviceLostMode($deviceId, $request);
    }

    public function resumeEmmDevice(int|string $deviceId): bool
    {
        return $this->apiClient->resumeEmmDevice($deviceId);
    }

    public function disableEmmDevice(int|string $deviceId): bool
    {
        return $this->apiClient->disableEmmDevice($deviceId);
    }

    public function syncDeviceDetail(int|string $deviceId): bool
    {
        return $this->apiClient->syncDeviceDetail($deviceId);
    }

    public function stopEmmDeviceLostMode(int|string $deviceId): bool
    {
        return $this->apiClient->stopEmmDeviceLostMode($deviceId);
    }

    public function clearEmmAppData(int|string $deviceId, string $installedAppIds): bool
    {
        return $this->apiClient->clearEmmAppData($deviceId, $installedAppIds);
    }

    public function submitEmmZteQuickUploadRecord(EmmZteQuickUploadRecordCreateRequest $request): bool
    {
        return $this->apiClient->submitEmmZteQuickUploadRecord($request);
    }

    public function getEmmDeviceDashboardDetail(
        int|string $deviceId,
    ): EmmDeviceDashboardDetailSearchResult {
        return $this->apiClient->getEmmDeviceDashboardDetail($deviceId);
    }

    public function getEmmDeviceDashboardMonitor(int|string $deviceId): EmmDeviceDashboardMonitor
    {
        return $this->apiClient->getEmmDeviceDashboardMonitor($deviceId);
    }

    public function searchDeviceInstalledApp(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        int|string|null $deviceId = null,
    ): EmmDeviceInstalledAppSearchResult {
        return $this->apiClient->searchDeviceInstalledApp(
            $pageNo,
            $pageSize,
            $orderBy,
            $deviceId,
        );
    }

    public function getEmmDeviceLocation(int|string $deviceId): EmmDeviceLocation
    {
        return $this->apiClient->getEmmDeviceLocation($deviceId);
    }

    public function getResellerEmmPolicy(string $resellerName): EmmPolicy
    {
        return $this->apiClient->getResellerEmmPolicy($resellerName);
    }

    public function createResellerEmmPolicy(ResellerEmmPolicyCreateRequest $request): bool
    {
        return $this->apiClient->createResellerEmmPolicy($request);
    }

    public function getMerchantEmmPolicy(string $resellerName, string $merchantName): EmmPolicy
    {
        return $this->apiClient->getMerchantEmmPolicy($resellerName, $merchantName);
    }

    public function createMerchantEmmPolicy(MerchantEmmPolicyCreateRequest $request): bool
    {
        return $this->apiClient->createMerchantEmmPolicy($request);
    }

    public function getDeviceEmmPolicy(string $serialNo): EmmPolicy
    {
        return $this->apiClient->getDeviceEmmPolicy($serialNo);
    }

    public function createDeviceEmmPolicy(DeviceEmmPolicyCreateRequest $request): bool
    {
        return $this->apiClient->createDeviceEmmPolicy($request);
    }

    public function getEntityAttribute(int|string $attributeId): EntityAttribute
    {
        return $this->apiClient->getEntityAttribute($attributeId);
    }

    public function searchEntityAttributes(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $key = null,
        ?string $entityType = null,
    ): EntityAttributeSearchResult {
        return $this->apiClient->searchEntityAttributes($pageNo, $pageSize, $orderBy, $key, $entityType);
    }

    public function createEntityAttribute(EntityAttributeCreateRequest $request): EntityAttribute
    {
        return $this->apiClient->createEntityAttribute($request);
    }

    public function updateEntityAttribute(
        int|string $attributeId,
        EntityAttributeUpdateRequest $request,
    ): EntityAttribute {
        return $this->apiClient->updateEntityAttribute($attributeId, $request);
    }

    public function updateEntityAttributeLabel(
        int|string $attributeId,
        EntityAttributeLabelUpdateRequest $request,
    ): bool {
        return $this->apiClient->updateEntityAttributeLabel($attributeId, $request);
    }

    public function deleteEntityAttribute(int|string $attributeId): bool
    {
        return $this->apiClient->deleteEntityAttribute($attributeId);
    }

    public function searchFactoryModels(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $factoryName = null,
        ?string $modelName = null,
        ?string $productType = null,
    ): FactoryModelSearchResult {
        return $this->apiClient->searchFactoryModels(
            $pageNo,
            $pageSize,
            $orderBy,
            $factoryName,
            $modelName,
            $productType,
        );
    }

    public function findDataFromInsight(
        string $queryCode,
        ?string $rangeType = null,
        array $customFilterList = [],
        ?int $pageNo = null,
        ?int $pageSize = null,
        string $timeZone = 'UTC',
    ): GoInsightDataQueryResult {
        return $this->apiClient->findDataFromInsight(
            $queryCode,
            $rangeType,
            $customFilterList,
            $pageNo,
            $pageSize,
            $timeZone,
        );
    }

    public function searchMerchant(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $name = null,
        ?string $status = null,
        bool $includeEntityAttribute = false,
    ): MerchantSearchResult {
        return $this->apiClient->searchMerchant(
            $pageNo,
            $pageSize,
            $orderBy,
            $name,
            $status,
            $includeEntityAttribute,
        );
    }

    public function getMerchant(int|string $merchantId): Merchant
    {
        return $this->apiClient->getMerchant($merchantId);
    }

    public function createMerchant(MerchantCreateRequest $merchantCreateRequest): Merchant
    {
        return $this->apiClient->createMerchant($merchantCreateRequest);
    }

    public function updateMerchant(int|string $merchantId, MerchantUpdateRequest $merchantUpdateRequest): Merchant
    {
        return $this->apiClient->updateMerchant($merchantId, $merchantUpdateRequest);
    }

    public function activateMerchant(int|string $merchantId): bool
    {
        return $this->apiClient->activateMerchant($merchantId);
    }

    public function disableMerchant(int|string $merchantId): bool
    {
        return $this->apiClient->disableMerchant($merchantId);
    }

    public function deleteMerchant(int|string $merchantId): bool
    {
        return $this->apiClient->deleteMerchant($merchantId);
    }

    public function replaceMerchantEmail(int|string $merchantId, string $email, bool $createUser): bool
    {
        return $this->apiClient->replaceMerchantEmail($merchantId, $email, $createUser);
    }

    public function getMerchantCategories(?string $name = null): array
    {
        return $this->apiClient->getMerchantCategories($name);
    }

    public function createMerchantCategory(MerchantCategoryRequest $request): MerchantCategory
    {
        return $this->apiClient->createMerchantCategory($request);
    }

    public function updateMerchantCategory(int|string $merchantCategoryId, MerchantCategoryRequest $request): MerchantCategory
    {
        return $this->apiClient->updateMerchantCategory($merchantCategoryId, $request);
    }

    public function deleteMerchantCategory(int|string $merchantCategoryId): bool
    {
        return $this->apiClient->deleteMerchantCategory($merchantCategoryId);
    }

    public function batchCreateMerchantCategory(array $requests, bool $skipExist = false): array
    {
        return $this->apiClient->batchCreateMerchantCategory($requests, $skipExist);
    }

    public function searchMerchantVariable(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        int|string|null $merchantId = null,
        ?string $packageName = null,
        ?string $key = null,
        ?string $source = null,
    ): ParameterVariableSearchResult {
        return $this->apiClient->searchMerchantVariable(
            $pageNo,
            $pageSize,
            $orderBy,
            $merchantId,
            $packageName,
            $key,
            $source,
        );
    }

    public function createMerchantVariable(MerchantVariableCreateRequest $request): bool
    {
        return $this->apiClient->createMerchantVariable($request);
    }

    public function updateMerchantVariable(
        int|string $merchantVariableId,
        MerchantVariableUpdateRequest $request,
    ): bool {
        return $this->apiClient->updateMerchantVariable(
            $merchantVariableId,
            $request,
        );
    }

    public function deleteMerchantVariable(int|string $merchantVariableId): bool
    {
        return $this->apiClient->deleteMerchantVariable($merchantVariableId);
    }

    public function batchDeletionMerchantVariable(
        ParameterVariableDeleteRequest $request,
    ): bool {
        return $this->apiClient->batchDeletionMerchantVariable($request);
    }

    public function searchParameterPushHistory(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $packageName = null,
        ?string $serialNo = null,
        ?string $pushStatus = null,
        DateTimeInterface|string|null $pushTime = null,
    ): ParameterPushHistorySearchResult {
        return $this->apiClient->searchParameterPushHistory(
            $pageNo,
            $pageSize,
            $packageName,
            $serialNo,
            $pushStatus,
            $pushTime,
        );
    }

    public function searchLatestParameterPushHistory(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $packageName = null,
        ?string $serialNo = null,
        ?string $pushStatus = null,
        DateTimeInterface|string|null $pushTime = null,
    ): ParameterPushHistorySearchResult {
        return $this->apiClient->searchLatestParameterPushHistory(
            $pageNo,
            $pageSize,
            $packageName,
            $serialNo,
            $pushStatus,
            $pushTime,
        );
    }

    public function searchOptimizedParameterPushHistory(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $packageName = null,
        ?string $serialNo = null,
        ?string $pushStatus = null,
        DateTimeInterface|string|null $pushTime = null,
    ): OptimizedParameterPushHistorySearchResult {
        return $this->apiClient->searchOptimizedParameterPushHistory(
            $pageNo,
            $pageSize,
            $packageName,
            $serialNo,
            $pushStatus,
            $pushTime,
        );
    }

    public function searchLatestOptimizedParameterPushHistory(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $packageName = null,
        ?string $serialNo = null,
        ?string $pushStatus = null,
        DateTimeInterface|string|null $pushTime = null,
    ): OptimizedParameterPushHistorySearchResult {
        return $this->apiClient->searchLatestOptimizedParameterPushHistory(
            $pageNo,
            $pageSize,
            $packageName,
            $serialNo,
            $pushStatus,
            $pushTime,
        );
    }

    public function searchReseller(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $name = null,
        ?string $status = null,
        bool $includeEntityAttribute = false,
    ): ResellerSearchResult {
        return $this->apiClient->searchReseller(
            $pageNo,
            $pageSize,
            $orderBy,
            $name,
            $status,
            $includeEntityAttribute,
        );
    }

    public function getReseller(int|string $resellerId): Reseller
    {
        return $this->apiClient->getReseller($resellerId);
    }

    public function createReseller(ResellerCreateRequest $request): Reseller
    {
        return $this->apiClient->createReseller($request);
    }

    public function updateReseller(int|string $resellerId, ResellerUpdateRequest $request): Reseller
    {
        return $this->apiClient->updateReseller($resellerId, $request);
    }

    public function activateReseller(int|string $resellerId): bool
    {
        return $this->apiClient->activateReseller($resellerId);
    }

    public function disableReseller(int|string $resellerId): bool
    {
        return $this->apiClient->disableReseller($resellerId);
    }

    public function deleteReseller(int|string $resellerId): bool
    {
        return $this->apiClient->deleteReseller($resellerId);
    }

    public function replaceResellerEmail(int|string $resellerId, string $email): bool
    {
        return $this->apiClient->replaceResellerEmail($resellerId, $email);
    }

    public function searchResellerRkiKeyList(
        int|string $resellerId,
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $rkiKey = null,
    ): ResellerRkiKeySearchResult {
        return $this->apiClient->searchResellerRkiKeyList($resellerId, $pageNo, $pageSize, $rkiKey);
    }

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
        return $this->apiClient->searchTerminal(
            $pageNo,
            $pageSize,
            $orderBy,
            $status,
            $snNameTID,
            $resellerName,
            $merchantName,
            $includeGeoLocation,
            $includeInstalledApks,
            $includeInstalledFirmware,
        );
    }

    /**
     * Retrieve a terminal by terminal ID.
     */
    public function getTerminal(
        int|string $terminalId,
        bool $includeDetailInfoList = false,
        bool $includeInstalledApks = false,
        bool $includeInstalledFirmware = false,
        bool $includeMasterTerminal = false,
    ): Terminal {
        return $this->apiClient->getTerminal(
            $terminalId,
            $includeDetailInfoList,
            $includeInstalledApks,
            $includeInstalledFirmware,
            $includeMasterTerminal,
        );
    }
 
    public function createTerminal(TerminalCreateRequest $terminalCreateRequest): Terminal
    {
        return $this->apiClient->createTerminal($terminalCreateRequest);
    }

    public function updateTerminal(int|string $terminalId, TerminalUpdateRequest $terminalUpdateRequest): Terminal
    {
        return $this->apiClient->updateTerminal($terminalId, $terminalUpdateRequest);
    }

    public function updateTerminalBySn(string $serialNo, TerminalUpdateRequest $terminalUpdateRequest): Terminal
    {
        return $this->apiClient->updateTerminalBySn($serialNo, $terminalUpdateRequest);
    }

    public function copyTerminal(TerminalCopyRequest $terminalCopyRequest): Terminal
    {
        return $this->apiClient->copyTerminal($terminalCopyRequest);
    }

    public function copyTerminalBySn(TerminalCopyRequest $terminalCopyRequest): Terminal
    {
        return $this->apiClient->copyTerminalBySn($terminalCopyRequest);
    }

    public function activateTerminal(int|string $terminalId): bool
    {
        return $this->apiClient->activateTerminal($terminalId);
    }

    public function activateTerminalBySn(string $serialNo): bool
    {
        return $this->apiClient->activateTerminalBySn($serialNo);
    }

    public function disableTerminal(int|string $terminalId): bool
    {
        return $this->apiClient->disableTerminal($terminalId);
    }

    public function disableTerminalBySn(string $serialNo): bool
    {
        return $this->apiClient->disableTerminalBySn($serialNo);
    }

    public function moveTerminal(int|string $terminalId, string $resellerName, string $merchantName): bool
    {
        return $this->apiClient->moveTerminal($terminalId, $resellerName, $merchantName);
    }

    public function moveTerminalBySn(string $serialNo, string $resellerName, string $merchantName): bool
    {
        return $this->apiClient->moveTerminalBySn($serialNo, $resellerName, $merchantName);
    }

    public function deleteTerminal(int|string $terminalId): bool
    {
        return $this->apiClient->deleteTerminal($terminalId);
    }

    public function deleteTerminalBySn(string $serialNo): bool
    {
        return $this->apiClient->deleteTerminalBySn($serialNo);
    }

    public function batchAddTerminalToGroup(array $terminalIds, array $groupIds): bool
    {
        return $this->apiClient->batchAddTerminalToGroup($terminalIds, $groupIds);
    }

    public function batchAddTerminalToGroupBySn(array $serialNos, array $groupIds): bool
    {
        return $this->apiClient->batchAddTerminalToGroupBySn($serialNos, $groupIds);
    }

    public function getTerminalConfig(int|string $terminalId): array
    {
        return $this->apiClient->getTerminalConfig($terminalId);
    }

    public function getTerminalConfigBySn(string $serialNo): array
    {
        return $this->apiClient->getTerminalConfigBySn($serialNo);
    }

    public function updateTerminalConfig(int|string $terminalId, array $configuration): bool
    {
        return $this->apiClient->updateTerminalConfig($terminalId, $configuration);
    }

    public function updateTerminalConfigBySn(string $serialNo, array $configuration): bool
    {
        return $this->apiClient->updateTerminalConfigBySn($serialNo, $configuration);
    }

    public function pushCmdToTerminal(int|string $terminalId, string $command): bool
    {
        return $this->apiClient->pushCmdToTerminal($terminalId, $command);
    }

    public function pushCmdToTerminalBySn(string $serialNo, string $command): bool
    {
        return $this->apiClient->pushCmdToTerminalBySn($serialNo, $command);
    }

    public function pushTerminalMessage(int|string $terminalId, string $title, string $content): bool
    {
        return $this->apiClient->pushTerminalMessage($terminalId, $title, $content);
    }

    public function pushTerminalMessageBySn(string $serialNo, string $title, string $content): bool
    {
        return $this->apiClient->pushTerminalMessageBySn($serialNo, $title, $content);
    }

    public function changeTerminalModel(int|string $terminalId, string $modelName): bool
    {
        return $this->apiClient->changeTerminalModel($terminalId, $modelName);
    }

    public function changeTerminalModelBySn(string $serialNo, string $modelName): bool
    {
        return $this->apiClient->changeTerminalModelBySn($serialNo, $modelName);
    }

    public function pushTerminalSetLauncherAction(int|string $terminalId, string $packageName): bool
    {
        return $this->apiClient->pushTerminalSetLauncherAction($terminalId, $packageName);
    }

    public function pushTerminalSetLauncherActionBySn(string $serialNo, string $packageName): bool
    {
        return $this->apiClient->pushTerminalSetLauncherActionBySn($serialNo, $packageName);
    }

    public function getTerminalNetwork(?string $serialNo = null, ?string $tid = null): TerminalNetwork
    {
        return $this->apiClient->getTerminalNetwork($serialNo, $tid);
    }

    public function getTerminalPed(int|string $terminalId): TerminalPed
    {
        return $this->apiClient->getTerminalPed($terminalId);
    }

    public function getTerminalPedBySn(string $serialNo): TerminalPed
    {
        return $this->apiClient->getTerminalPedBySn($serialNo);
    }

    public function getTerminalSystemUsageById(int|string $terminalId): TerminalSystemUsage
    {
        return $this->apiClient->getTerminalSystemUsageById($terminalId);
    }

    public function getTerminalSystemUsageBySn(string $serialNo): TerminalSystemUsage
    {
        return $this->apiClient->getTerminalSystemUsageBySn($serialNo);
    }

    public function collectTerminalLog(int|string $terminalId, string $type, ?string $beginDate = null, ?string $endDate = null): bool
    {
        return $this->apiClient->collectTerminalLog($terminalId, $type, $beginDate, $endDate);
    }

    public function collectTerminalLogBySn(string $serialNo, string $type, ?string $beginDate = null, ?string $endDate = null): bool
    {
        return $this->apiClient->collectTerminalLogBySn($serialNo, $type, $beginDate, $endDate);
    }

    public function searchTerminalLog(int|string $terminalId, int $pageNo = 1, int $pageSize = 10): TerminalLogSearchResult
    {
        return $this->apiClient->searchTerminalLog($terminalId, $pageNo, $pageSize);
    }

    public function searchTerminalLogBySn(string $serialNo, int $pageNo = 1, int $pageSize = 10): TerminalLogSearchResult
    {
        return $this->apiClient->searchTerminalLogBySn($serialNo, $pageNo, $pageSize);
    }

    public function downloadTerminalLog(int|string $terminalId, int|string $terminalLogId): TerminalLogDownloadTask
    {
        return $this->apiClient->downloadTerminalLog($terminalId, $terminalLogId);
    }

    public function downloadTerminalLogBySn(string $serialNo, int|string $terminalLogId): TerminalLogDownloadTask
    {
        return $this->apiClient->downloadTerminalLogBySn($serialNo, $terminalLogId);
    }

    public function createTerminalApk(CreateTerminalApkRequest $request): TerminalApk
    {
        return $this->apiClient->createTerminalApk($request);
    }

    public function createTerminalApkWithPartialParams(CreateTerminalApkPartialParamRequest $request): TerminalApk
    {
        return $this->apiClient->createTerminalApkWithPartialParams($request);
    }

    public function searchTerminalApk(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $terminalTid = null,
        ?string $appPackageName = null,
        ?string $status = null,
        ?string $serialNo = null,
        array $pidList = [],
    ): TerminalApkSearchResult {
        return $this->apiClient->searchTerminalApk(
            $pageNo,
            $pageSize,
            $orderBy,
            $terminalTid,
            $appPackageName,
            $status,
            $serialNo,
            $pidList,
        );
    }

    public function getTerminalApk(int|string $terminalApkId, array $pidList = []): TerminalApk
    {
        return $this->apiClient->getTerminalApk($terminalApkId, $pidList);
    }

    public function disableApkPush(UpdateTerminalApkRequest $request): bool
    {
        return $this->apiClient->disableApkPush($request);
    }

    public function uninstallApk(UpdateTerminalApkRequest $request): bool
    {
        return $this->apiClient->uninstallApk($request);
    }

    public function deleteTerminalApk(int|string $terminalApkId): bool
    {
        return $this->apiClient->deleteTerminalApk($terminalApkId);
    }

    public function searchTerminalApkParameter(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $templateName = null,
        string $packageName = '',
        string $versionName = '',
    ): ApkParameterSearchResult {
        return $this->apiClient->searchTerminalApkParameter(
            $pageNo,
            $pageSize,
            $orderBy,
            $templateName,
            $packageName,
            $versionName,
        );
    }

    public function getTerminalApkParameter(
        int|string $apkParameterId,
        array $pidList = [],
    ): ApkParameter {
        return $this->apiClient->getTerminalApkParameter($apkParameterId, $pidList);
    }

    public function createApkParameter(CreateApkParameterRequest $request): bool
    {
        return $this->apiClient->createApkParameter($request);
    }

    public function updateApkParameter(
        int|string $apkParameterId,
        UpdateApkParameterRequest $request,
    ): bool {
        return $this->apiClient->updateApkParameter($apkParameterId, $request);
    }

    public function deleteApkParameter(int|string $apkParameterId): bool
    {
        return $this->apiClient->deleteApkParameter($apkParameterId);
    }

    public function verifyTerminalEstate(string $serialNo): bool
    {
        return $this->apiClient->verifyTerminalEstate($serialNo);
    }

    public function pushFirmwareToTerminal(PushFirmwareToTerminalRequest $request): PushFirmwareTask
    {
        return $this->apiClient->pushFirmwareToTerminal($request);
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
        return $this->apiClient->searchPushFirmwareTasks(
            $pageNo,
            $pageSize,
            $orderBy,
            $terminalTid,
            $fmName,
            $status,
            $serialNo,
        );
    }

    public function getPushFirmwareTask(int|string $pushFirmwareTaskId): PushFirmwareTask
    {
        return $this->apiClient->getPushFirmwareTask($pushFirmwareTaskId);
    }

    public function disablePushFirmwareTask(DisablePushFirmwareTaskRequest $request): bool
    {
        return $this->apiClient->disablePushFirmwareTask($request);
    }

    public function deleteTerminalFirmware(int|string $terminalFirmwareId): bool
    {
        return $this->apiClient->deleteTerminalFirmware($terminalFirmwareId);
    }

    public function searchGeoFenceWhiteList(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $serialNo = null,
    ): TerminalGeoFenceWhiteListSearchResult {
        return $this->apiClient->searchGeoFenceWhiteList($pageNo, $pageSize, $orderBy, $serialNo);
    }

    public function createGeoFenceWhiteList(TerminalGeoFenceWhiteListRequest $request): bool
    {
        return $this->apiClient->createGeoFenceWhiteList($request);
    }

    public function deleteGeoFenceWhiteList(TerminalGeoFenceWhiteListRequest $request): bool
    {
        return $this->apiClient->deleteGeoFenceWhiteList($request);
    }

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
        return $this->apiClient->searchTerminalGroup(
            $pageNo,
            $pageSize,
            $orderBy,
            $status,
            $name,
            $resellerNames,
            $modelNames,
            $isDynamic,
        );
    }

    public function getTerminalGroup(int|string $groupId): TerminalGroup
    {
        return $this->apiClient->getTerminalGroup($groupId);
    }

    public function createTerminalGroup(TerminalGroupRequest $request): TerminalGroup
    {
        return $this->apiClient->createTerminalGroup($request);
    }

    public function updateTerminalGroup(int|string $groupId, TerminalGroupRequest $request): TerminalGroup
    {
        return $this->apiClient->updateTerminalGroup($groupId, $request);
    }

    public function activateTerminalGroup(int|string $groupId): bool
    {
        return $this->apiClient->activateTerminalGroup($groupId);
    }

    public function disableTerminalGroup(int|string $groupId): bool
    {
        return $this->apiClient->disableTerminalGroup($groupId);
    }

    public function deleteTerminalGroup(int|string $groupId): bool
    {
        return $this->apiClient->deleteTerminalGroup($groupId);
    }

    public function addTerminalToGroup(int|string $groupId, array $terminalIds): bool
    {
        return $this->apiClient->addTerminalToGroup($groupId, $terminalIds);
    }

    public function removeTerminalOutGroup(int|string $groupId, array $terminalIds): bool
    {
        return $this->apiClient->removeTerminalOutGroup($groupId, $terminalIds);
    }

    public function getTerminalGroupApk(int|string $groupApkId, array $pidList = []): TerminalGroupApk
    {
        return $this->apiClient->getTerminalGroupApk($groupApkId, $pidList);
    }

    public function searchTerminalGroupApk(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        int|string|null $groupId = null,
        ?bool $pendingOnly = null,
        ?bool $historyOnly = null,
        ?string $keyWords = null,
    ): TerminalGroupApkSearchResult {
        return $this->apiClient->searchTerminalGroupApk(
            $pageNo,
            $pageSize,
            $orderBy,
            $groupId,
            $pendingOnly,
            $historyOnly,
            $keyWords,
        );
    }

    public function createAndActiveGroupApk(CreateTerminalGroupApkRequest $request): TerminalGroupApk
    {
        return $this->apiClient->createAndActiveGroupApk($request);
    }

    public function createAndActiveGroupApkWithPartialParams(
        CreateTerminalGroupApkPartialParamRequest $request,
    ): TerminalGroupApk {
        return $this->apiClient->createAndActiveGroupApkWithPartialParams($request);
    }

    public function suspendTerminalGroupApk(int|string $groupApkId): TerminalGroupApk
    {
        return $this->apiClient->suspendTerminalGroupApk($groupApkId);
    }

    public function deleteTerminalGroupApk(int|string $groupApkId): bool
    {
        return $this->apiClient->deleteTerminalGroupApk($groupApkId);
    }

    public function searchGroupPushRkiTask(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        int|string|null $groupId = null,
        ?bool $pendingOnly = null,
        ?bool $historyOnly = null,
        ?string $keyWords = null,
    ): TerminalGroupRkiSearchResult {
        return $this->apiClient->searchGroupPushRkiTask(
            $pageNo,
            $pageSize,
            $orderBy,
            $groupId,
            $pendingOnly,
            $historyOnly,
            $keyWords,
        );
    }

    public function getGroupPushRkiTask(int|string $groupPushRkiTaskId): TerminalGroupRki
    {
        return $this->apiClient->getGroupPushRkiTask($groupPushRkiTaskId);
    }

    public function pushRkiKey2Group(CreateTerminalGroupRkiRequest $request): TerminalGroupRki
    {
        return $this->apiClient->pushRkiKey2Group($request);
    }

    public function disableGroupRkiPushTask(int|string $groupPushRkiTaskId): TerminalGroupRki
    {
        return $this->apiClient->disableGroupRkiPushTask($groupPushRkiTaskId);
    }

    public function pushRkiKey2Terminal(PushRki2TerminalRequest $request): PushRkiTask
    {
        return $this->apiClient->pushRkiKey2Terminal($request);
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
        return $this->apiClient->searchPushRkiTasks(
            $pageNo,
            $pageSize,
            $orderBy,
            $terminalTid,
            $rkiKey,
            $status,
            $serialNo,
        );
    }

    public function getPushRkiTask(int|string $pushRkiTaskId): PushRkiTask
    {
        return $this->apiClient->getPushRkiTask($pushRkiTaskId);
    }

    public function disablePushRkiTask(DisablePushRkiTaskRequest $request): bool
    {
        return $this->apiClient->disablePushRkiTask($request);
    }

    public function deleteTerminalRki(int|string $terminalRkiId): bool
    {
        return $this->apiClient->deleteTerminalRki($terminalRkiId);
    }

    public function getTerminalVariable(
        int $pageNo = 1,
        int $pageSize = 10,
        ?string $orderBy = null,
        ?string $tid = null,
        ?string $serialNo = null,
        ?string $packageName = null,
        ?string $key = null,
        ?string $source = null,
    ): ParameterVariableSearchResult {
        return $this->apiClient->getTerminalVariable(
            $pageNo,
            $pageSize,
            $orderBy,
            $tid,
            $serialNo,
            $packageName,
            $key,
            $source,
        );
    }

    public function createTerminalVariable(TerminalParameterVariableRequest $request): bool
    {
        return $this->apiClient->createTerminalVariable($request);
    }

    public function updateTerminalVariable(
        int|string $terminalVariableId,
        ParameterVariable $request,
    ): bool {
        return $this->apiClient->updateTerminalVariable($terminalVariableId, $request);
    }

    public function deleteTerminalVariable(int|string $terminalVariableId): bool
    {
        return $this->apiClient->deleteTerminalVariable($terminalVariableId);
    }

    public function batchDeletionTerminalVariable(ParameterVariableDeleteRequest $request): bool
    {
        return $this->apiClient->batchDeletionTerminalVariable($request);
    }
}
