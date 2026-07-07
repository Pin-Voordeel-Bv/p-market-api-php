<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalApk
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $apkPackageName = null,
        public ?string $apkVersionName = null,
        public int|string|null $apkVersionCode = null,
        public ?string $terminalSN = null,
        public ?string $status = null,
        public int|string|null $actionStatus = null,
        public ?int $errorCode = null,
        public int|string|null $activatedDate = null,
        public ?bool $forceUpdate = null,
        public ?bool $wifiOnly = null,
        public int|string|null $effectiveTime = null,
        public int|string|null $expiredTime = null,
        public int|string|null $actionTime = null,
        public TerminalApkParam|array|null $terminalApkParam = null,
    ) {
    }
}
