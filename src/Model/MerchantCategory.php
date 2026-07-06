<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class MerchantCategory
{
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?string $remarks = null,
    ) {
    }
}
