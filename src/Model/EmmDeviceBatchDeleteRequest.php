<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmDeviceBatchDeleteRequest
{
    public function __construct(
        public array $deviceIds,
    ) {
    }
}
