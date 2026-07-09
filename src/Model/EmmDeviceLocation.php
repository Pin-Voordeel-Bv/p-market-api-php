<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmDeviceLocation
{
    public function __construct(
        public int|string|null $terminalId = null,
        public int|float|string|null $lat = null,
        public int|float|string|null $lng = null,
        public int|string|null $updatedDate = null,
    ) {}
}
