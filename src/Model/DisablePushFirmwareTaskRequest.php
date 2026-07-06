<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class DisablePushFirmwareTaskRequest
{
    public function __construct(
        public string $fmName,
        public ?string $tid = null,
        public ?string $serialNo = null,
    ) {
    }
}
