<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalNetwork
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $tid = null,
        public ?string $serialNo = null,
        public ?string $status = null,
        public int|float|null $battery = null,
        public ?int $onlineStatus = null,
        public ?string $network = null,
        public ?string $macAddress = null,
    ) {
    }
}
