<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmAppAvailableTestVersion
{
    public function __construct(
        public int|string|null $trackId = null,
        public ?string $trackAlias = null,
        public ?string $versionName = null,
    ) {
    }
}
