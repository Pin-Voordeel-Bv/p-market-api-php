<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class GoInsightDataQueryResult
{
    public function __construct(
        public ?string $worksheetName = null,
        public array $columns = [],
        public array $rows = [],
        public ?bool $hasNext = null,
        public ?int $offset = null,
        public ?int $limit = null,
    ) {}
}
