<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class FactoryModel
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $name = null,
        public ?string $productTypeLabel = null,
        public ?string $parentProductTypeLabel = null,
        public ?string $productType = null,
        public ?string $parentProductType = null,
    ) {
    }
}
