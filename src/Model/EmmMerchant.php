<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmMerchant
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $name = null,
    ) {
    }
}
