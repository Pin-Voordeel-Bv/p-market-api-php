<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class GoInsightColumn
{
    public function __construct(
        public ?string $colName = null,
        public ?string $displayName = null,
        public ?string $type = null,
    ) {}
}
