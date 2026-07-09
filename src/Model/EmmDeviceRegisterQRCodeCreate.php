<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmDeviceRegisterQRCodeCreate
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $marketName = null,
        public ?string $resellerName = null,
        public ?string $merchantName = null,
        public ?string $deviceType = null,
        public ?string $registerQRCode = null,
        public int|string|null $expireDate = null,
    ) {
    }
}
