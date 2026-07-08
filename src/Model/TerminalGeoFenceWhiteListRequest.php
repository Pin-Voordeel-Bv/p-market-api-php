<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalGeoFenceWhiteListRequest
{
    public function __construct(
        public string $serialNo,
    ) {
    }
}
