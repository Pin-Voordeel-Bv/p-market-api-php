<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmAppCreateRequest
{
    public function __construct(
        public string $resellerName,
        public string $packageName,
    ) {
    }
}
