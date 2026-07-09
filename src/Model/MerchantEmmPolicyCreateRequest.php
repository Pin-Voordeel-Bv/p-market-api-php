<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class MerchantEmmPolicyCreateRequest
{
    public function __construct(
        public string $resellerName,
        public string $merchantName,
        public bool $inheritFlag,
        public array $contentInfo = [],
        public array $lockedPolicyList = [],
    ) {
    }
}
