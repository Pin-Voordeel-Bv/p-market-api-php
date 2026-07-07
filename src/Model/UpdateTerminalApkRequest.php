<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class UpdateTerminalApkRequest
{
    public function __construct(
        public string $packageName,
        public ?string $tid = null,
        public ?string $serialNo = null,
    ) {
    }
}
