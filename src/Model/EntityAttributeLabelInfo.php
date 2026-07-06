<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EntityAttributeLabelInfo
{
    public function __construct(
        public ?string $locale = null,
        public ?string $label = null,
    ) {
    }
}
