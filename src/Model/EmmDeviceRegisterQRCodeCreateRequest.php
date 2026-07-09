<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmDeviceRegisterQRCodeCreateRequest
{
    public function __construct(
        public string $resellerName,
        public string $merchantName,
        public string $type,
        public int|string $expireDate,
    ) {
    }
}
