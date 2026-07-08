<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class GoInsightCustomFilter
{
    public function __construct(
        public string $cloName,
        public string $filterValue,
    ) {}
}
