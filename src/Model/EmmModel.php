<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmModel
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $name = null,
        public ?string $mfrName = null,
    ) {
    }
}
