<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmDeviceDashboardDetail
{
    public function __construct(
        public int|string|null $terminalId = null,
        public ?string $key = null,
        public ?string $value = null,
        public int|string|null $syncDate = null,
    ) {}
}
