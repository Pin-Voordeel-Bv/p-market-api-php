<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class MerchantCategoryRequest
{
    public function __construct(
        public string $name,
        public ?string $remarks = null,
    ) {
    }
}
