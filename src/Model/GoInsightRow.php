<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class GoInsightRow
{
    public function __construct(
        public ?string $colName = null,
        public ?string $value = null,
    ) {}
}
