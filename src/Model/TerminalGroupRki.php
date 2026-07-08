<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalGroupRki
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $rkiKey = null,
        public int|string|null $activatedDate = null,
        public int|string|null $effectiveTime = null,
        public ?string $status = null,
        public int|string|null $actionStatus = null,
        public ?int $errorCode = null,
        public ?int $pendingCount = null,
        public ?int $successCount = null,
        public ?int $failedCount = null,
        public ?bool $completed = null,
        public ?int $pushLimit = null,
        public ?string $remarks = null,
    ) {
    }
}
