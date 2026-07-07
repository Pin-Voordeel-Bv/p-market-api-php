<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalGroupApkParam
{
    public function __construct(
        public ?string $paramTemplateName = null,
        public array $configuredParameters = [],
        public ?int $pendingCount = null,
        public ?int $successCount = null,
        public ?int $failedCount = null,
        public ?int $filteredCount = null,
    ) {
    }
}
