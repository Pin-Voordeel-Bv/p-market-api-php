<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmDeviceResetPasswordRequest
{
    public function __construct(
        public string $password,
        public bool $lockNow,
    ) {
    }
}
