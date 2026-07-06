<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class SimpleReseller
{
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
    ) {
    }
}
