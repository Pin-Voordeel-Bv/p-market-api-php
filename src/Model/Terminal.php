<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class Terminal
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $name = null,
        public ?string $tid = null,
        public ?string $serialNo = null,
        public ?string $status = null,
        public ?string $merchantName = null,
        public ?string $modelName = null,
        public ?string $resellerName = null,
        public ?string $location = null,
        public ?string $remark = null,
        public int|string|null $createdDate = null,
        public int|string|null $updatedDate = null,
        public int|string|null $lastActiveTime = null,
        public int|string|null $lastAccessTime = null,
        public ?array $terminalDetail = null,
        public ?array $terminalAccessory = null,
        public ?array $installedApks = null,
        public ?array $installedFirmware = null,
        public ?string $masterTerminalSerialNo = null,
    ) {
    }
}
