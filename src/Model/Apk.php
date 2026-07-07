<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class Apk
{
    public function __construct(
        public ?string $status = null,
        public int|string|null $versionCode = null,
        public ?string $versionName = null,
        public ?string $apkType = null,
        public ?string $apkFileType = null,
        public ApkFile|array|null $apkFile = null,
        public ?string $osType = null,
    ) {
    }
}
