<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalGroupApk
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $apkPackageName = null,
        public ?string $apkVersionName = null,
        public int|string|null $apkVersionCode = null,
        public int|string|null $effectiveTime = null,
        public int|string|null $expiredTime = null,
        public int|string|null $updatedDate = null,
        public int|string|null $actionStatus = null,
        public ?string $status = null,
        public ?int $pendingCount = null,
        public ?int $successCount = null,
        public ?int $failedCount = null,
        public ?int $filteredCount = null,
        public TerminalGroupApkParam|array|null $groupApkParam = null,
    ) {
    }
}
