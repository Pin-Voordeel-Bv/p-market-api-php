<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class ResellerEmmPolicyCreateRequest
{
    public function __construct(
        public string $resellerName,
        public bool $inheritFlag,
        public array $contentInfo = [],
        public array $lockedPolicyList = [],
    ) {
    }
}
