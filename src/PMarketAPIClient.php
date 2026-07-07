<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI;

use GuzzleHttp\Client;
use PinVandaag\PMarketAPI\Client\APIClient;
use PinVandaag\PMarketAPI\Model\CreateTerminalApkPartialParamRequest;
use PinVandaag\PMarketAPI\Model\CreateTerminalApkRequest;
use PinVandaag\PMarketAPI\Model\CreateTerminalGroupApkPartialParamRequest;
use PinVandaag\PMarketAPI\Model\CreateTerminalGroupApkRequest;
use PinVandaag\PMarketAPI\Model\DisablePushFirmwareTaskRequest;
use PinVandaag\PMarketAPI\Model\EntityAttribute;
use PinVandaag\PMarketAPI\Model\EntityAttributeCreateRequest;
use PinVandaag\PMarketAPI\Model\EntityAttributeLabelUpdateRequest;
use PinVandaag\PMarketAPI\Model\EntityAttributeSearchResult;
use PinVandaag\PMarketAPI\Model\EntityAttributeUpdateRequest;
use PinVandaag\PMarketAPI\Model\FactoryModelSearchResult;
use PinVandaag\PMarketAPI\Model\Merchant;
use PinVandaag\PMarketAPI\Model\MerchantCategory;
use PinVandaag\PMarketAPI\Model\MerchantCategoryRequest;
use PinVandaag\PMarketAPI\Model\MerchantCreateRequest;
use PinVandaag\PMarketAPI\Model\MerchantSearchResult;
use PinVandaag\PMarketAPI\Model\MerchantUpdateRequest;
use PinVandaag\PMarketAPI\Model\PushFirmwareTask;
use PinVandaag\PMarketAPI\Model\PushFirmwareTaskSearchResult;
use PinVandaag\PMarketAPI\Model\PushFirmwareToTerminalRequest;
use PinVandaag\PMarketAPI\Model\Reseller;
use PinVandaag\PMarketAPI\Model\ResellerCreateRequest;
use PinVandaag\PMarketAPI\Model\ResellerRkiKeySearchResult;
use PinVandaag\PMarketAPI\Model\ResellerSearchResult;
use PinVandaag\PMarketAPI\Model\ResellerUpdateRequest;
use PinVandaag\PMarketAPI\Model\Terminal;
use PinVandaag\PMarketAPI\Model\TerminalApk;
use PinVandaag\PMarketAPI\Model\TerminalApkSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalCopyRequest;
use PinVandaag\PMarketAPI\Model\TerminalCreateRequest;
use PinVandaag\PMarketAPI\Model\TerminalGroup;
use PinVandaag\PMarketAPI\Model\TerminalGroupApk;
use PinVandaag\PMarketAPI\Model\TerminalGroupApkSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalGroupRequest;
use PinVandaag\PMarketAPI\Model\TerminalGroupSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalLogDownloadTask;
use PinVandaag\PMarketAPI\Model\TerminalLogSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalNetwork;
use PinVandaag\PMarketAPI\Model\TerminalPed;
use PinVandaag\PMarketAPI\Model\TerminalSearchResult;
use PinVandaag\PMarketAPI\Model\TerminalSystemUsage;
use PinVandaag\PMarketAPI\Model\TerminalUpdateRequest;
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
}
