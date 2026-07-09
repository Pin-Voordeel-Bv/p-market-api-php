<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\DeviceEmmPolicyCreateRequest;
use PinVandaag\PMarketAPI\Model\EmmPolicy;
use PinVandaag\PMarketAPI\Model\MerchantEmmPolicyCreateRequest;
use PinVandaag\PMarketAPI\Model\ResellerEmmPolicyCreateRequest;

trait EmmPolicyApiTrait
{
    public function getResellerEmmPolicy(string $resellerName): EmmPolicy
    {
        $this->assertEmmPolicyString($resellerName, 'resellerName', 64);

        return $this->getResultData(
            endpoint: '/v1/3rdsys/emm/policy/reseller',
            responseClass: EmmPolicy::class,
            actionDescription: 'get P Market reseller EMM policy',
            query: ['resellerName' => $resellerName],
        );
    }

    public function createResellerEmmPolicy(ResellerEmmPolicyCreateRequest $request): bool
    {
        $this->assertResellerEmmPolicyCreateRequest($request);

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/emm/policy/reseller',
            actionDescription: 'create P Market reseller EMM policy',
            headers: ['Content-Type' => 'application/json'],
            body: $this->resellerEmmPolicyPayload($request),
        );

        return true;
    }

    public function getMerchantEmmPolicy(string $resellerName, string $merchantName): EmmPolicy
    {
        $this->assertEmmPolicyString($resellerName, 'resellerName', 64);
        $this->assertEmmPolicyString($merchantName, 'merchantName', 128);

        return $this->getResultData(
            endpoint: '/v1/3rdsys/emm/policy/merchant',
            responseClass: EmmPolicy::class,
            actionDescription: 'get P Market merchant EMM policy',
            query: [
                'resellerName' => $resellerName,
                'merchantName' => $merchantName,
            ],
        );
    }

    public function createMerchantEmmPolicy(MerchantEmmPolicyCreateRequest $request): bool
    {
        $this->assertMerchantEmmPolicyCreateRequest($request);

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/emm/policy/merchant',
            actionDescription: 'create P Market merchant EMM policy',
            headers: ['Content-Type' => 'application/json'],
            body: $this->merchantEmmPolicyPayload($request),
        );

        return true;
    }

    public function getDeviceEmmPolicy(string $serialNo): EmmPolicy
    {
        $this->assertEmmPolicyString($serialNo, 'serialNo', 16);

        return $this->getResultData(
            endpoint: '/v1/3rdsys/emm/policy/device',
            responseClass: EmmPolicy::class,
            actionDescription: 'get P Market device EMM policy',
            query: ['serialNo' => $serialNo],
        );
    }

    public function createDeviceEmmPolicy(DeviceEmmPolicyCreateRequest $request): bool
    {
        $this->assertDeviceEmmPolicyCreateRequest($request);

        $this->emptyResult(
            method: 'POST',
            endpoint: '/v1/3rdsys/emm/policy/device',
            actionDescription: 'create P Market device EMM policy',
            headers: ['Content-Type' => 'application/json'],
            body: $this->deviceEmmPolicyPayload($request),
        );

        return true;
    }

    private function assertResellerEmmPolicyCreateRequest(ResellerEmmPolicyCreateRequest $request): void
    {
        $this->assertEmmPolicyString($request->resellerName, 'resellerName', 64);
        $this->assertEmmPolicyContent($request->inheritFlag, $request->contentInfo);
    }

    private function assertMerchantEmmPolicyCreateRequest(MerchantEmmPolicyCreateRequest $request): void
    {
        $this->assertEmmPolicyString($request->resellerName, 'resellerName', 64);
        $this->assertEmmPolicyString($request->merchantName, 'merchantName', 128);
        $this->assertEmmPolicyContent($request->inheritFlag, $request->contentInfo);
    }

    private function assertDeviceEmmPolicyCreateRequest(DeviceEmmPolicyCreateRequest $request): void
    {
        $this->assertEmmPolicyString($request->serialNo, 'serialNo', 16);
        $this->assertEmmPolicyContent($request->inheritFlag, $request->contentInfo);
    }

    private function assertEmmPolicyString(string $value, string $field, int $maxLength): void
    {
        if (trim($value) === '') {
            throw new PMarketAPIException(sprintf('Parameter %s cannot be null!', $field));
        }

        if (mb_strlen($value) > $maxLength) {
            throw new PMarketAPIException(sprintf('Parameter %s is too long, maxlength is %d!', $field, $maxLength));
        }
    }

    private function assertEmmPolicyContent(bool $inheritFlag, array $contentInfo): void
    {
        if (!$inheritFlag && $contentInfo === []) {
            throw new PMarketAPIException('Parameter contentInfo cannot be null!');
        }
    }

    private function resellerEmmPolicyPayload(ResellerEmmPolicyCreateRequest $request): array
    {
        return $this->baseEmmPolicyPayload([
            'resellerName' => $request->resellerName,
            'inheritFlag' => $request->inheritFlag,
            'contentInfo' => $request->contentInfo,
            'lockedPolicyList' => $request->lockedPolicyList,
        ]);
    }

    private function merchantEmmPolicyPayload(MerchantEmmPolicyCreateRequest $request): array
    {
        return $this->baseEmmPolicyPayload([
            'resellerName' => $request->resellerName,
            'merchantName' => $request->merchantName,
            'inheritFlag' => $request->inheritFlag,
            'contentInfo' => $request->contentInfo,
            'lockedPolicyList' => $request->lockedPolicyList,
        ]);
    }

    private function deviceEmmPolicyPayload(DeviceEmmPolicyCreateRequest $request): array
    {
        return $this->baseEmmPolicyPayload([
            'serialNo' => $request->serialNo,
            'inheritFlag' => $request->inheritFlag,
            'contentInfo' => $request->contentInfo,
            'lockedPolicyList' => $request->lockedPolicyList,
        ]);
    }

    private function baseEmmPolicyPayload(array $payload): array
    {
        return array_filter(
            $payload,
            static fn ($value): bool => $value !== null && $value !== []
        );
    }
}
