<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class AppCost
{
    public function __construct(
        public ?bool $paid = null,
        public int|string|null $chargeType = null,
        public int|float|string|null $price = null,
        public ?string $text = null,
        public int|string|null $currency = null,
        public ?int $freeTrialDay = null,
    ) {
    }
}
