<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmAppAvailableTestVersionList
{
    public function __construct(
        public array $appAvailableTestVersionList = [],
    ) {
    }
}
