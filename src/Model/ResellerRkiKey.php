<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class ResellerRkiKey
{
    public function __construct(
        public ?string $keyId = null,
    ) {
    }
}
