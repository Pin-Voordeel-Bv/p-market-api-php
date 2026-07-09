<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmDeviceBatchMoveRequest
{
    public function __construct(
        public array $deviceIds,
        public string $resellerName,
        public string $merchantName,
    ) {
    }
}
