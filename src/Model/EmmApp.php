<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmApp
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $name = null,
        public ?string $packageName = null,
        public ?string $iconUrl = null,
        public ?string $type = null,
        public ?string $developerName = null,
        public ?bool $supportManagedConfig = null,
    ) {
    }
}
