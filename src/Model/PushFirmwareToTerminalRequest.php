<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class PushFirmwareToTerminalRequest
{
    public function __construct(
        public string $fmName,
        public ?string $tid = null,
        public ?string $serialNo = null,
        public ?bool $wifiOnly = null,
        public ?string $effectiveTime = null,
        public ?string $expiredTime = null,
    ) {
    }
}
