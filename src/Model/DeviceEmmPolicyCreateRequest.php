<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class DeviceEmmPolicyCreateRequest
{
    public function __construct(
        public string $serialNo,
        public bool $inheritFlag,
        public array $contentInfo = [],
        public array $lockedPolicyList = [],
    ) {
    }
}
