<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class MerchantVariableUpdateRequest
{
    public function __construct(
        public ?string $packageName = null,
        public ?string $type = null,
        public ?string $key = null,
        public ?string $value = null,
        public ?string $remarks = null,
    ) {
    }
}
