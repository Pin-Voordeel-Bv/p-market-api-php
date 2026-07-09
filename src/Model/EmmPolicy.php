<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmPolicy
{
    public function __construct(
        public ?int $customPolicyCount = null,
        public ?string $name = null,
        public array $contentInfo = [],
        public array $lockedPolicyList = [],
        public ?bool $inheritFlag = null,
    ) {
    }
}
