<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalSystemUsage
{
    public function __construct(
        public int|float|null $totalCpuUsage = null,
        public int|string|null $totalStorageUsage = null,
        public int|string|null $totalRamUsage = null,
        public int|string|null $totalRAM = null,
        public int|string|null $totalStorage = null,
    ) {
    }
}
