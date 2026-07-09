<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmDeviceUpdateRequest
{
    public function __construct(
        public string $deviceName,
        public string $resellerName,
        public string $merchantName,
    ) {
    }
}
