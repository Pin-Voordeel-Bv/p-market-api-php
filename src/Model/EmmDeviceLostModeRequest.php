<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmDeviceLostModeRequest
{
    public function __construct(
        public string $lostMessage,
        public string $lostPhoneNumber,
    ) {
    }
}
