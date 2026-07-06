<?php
declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalGroupSearchResult
{
    public function __construct(
        public int $pageNo = 1,
        public int $limit = 0,
        public int $totalCount = 0,
        public bool $hasNext = false,
        public array $dataSet = [],
    ) {}
}
