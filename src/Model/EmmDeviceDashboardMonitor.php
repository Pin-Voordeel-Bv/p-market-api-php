<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmDeviceDashboardMonitor
{
    public function __construct(
        public int|string|null $terminalId = null,
        public int|float|string|null $battery = null,
        public int|string|null $ramUsed = null,
        public int|string|null $storageUsed = null,
        public int|string|null $syncDate = null,
    ) {}
}
