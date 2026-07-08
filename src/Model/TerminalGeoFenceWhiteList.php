<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalGeoFenceWhiteList
{
    public function __construct(
        public int|string|null $terminalId = null,
        public ?string $serialNo = null,
    ) {
    }
}
