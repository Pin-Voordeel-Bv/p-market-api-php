<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmDeviceInstalledApp
{
    public function __construct(
        public int|string|null $id = null,
        public int|string|null $terminalId = null,
        public ?string $name = null,
        public ?string $packageName = null,
        public ?string $version = null,
        public ?string $type = null,
        public int|string|null $size = null,
        public ?string $iconUrl = null,
        public int|string|null $installTime = null,
        public int|string|null $lastTimeUpdate = null,
        public ?bool $isLauncher = null,
        public ?bool $isDefaultLauncher = null,
    ) {}
}
