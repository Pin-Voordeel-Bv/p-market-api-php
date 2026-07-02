<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalCopyRequest
{
    public function __construct(
        public string $name,
        public ?string $serialNo = null,
        public ?string $status = null,
        public int|string|null $terminalId = null,
        public ?string $sourceSerialNo = null,
        public ?string $tid = null,
    ) {
    }
}
