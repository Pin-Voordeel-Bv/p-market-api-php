<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class App
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $name = null,
        public ?string $packageName = null,
        public ?string $status = null,
        public ?string $osType = null,
        public ?bool $specificReseller = null,
        public ?bool $specificMerchantCategory = null,
        public int|string|null $chargeType = null,
        public int|float|string|null $price = null,
        public ?string $text = null,
        public int|string|null $downloads = null,
        public array $apkList = [],
        public array $entityAttributeValues = [],
    ) {
    }
}
