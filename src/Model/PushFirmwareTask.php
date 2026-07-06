<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class PushFirmwareTask
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $fmName = null,
        public ?string $terminalSN = null,
        public ?string $status = null,
        public int|string|null $actionStatus = null,
        public ?int $errorCode = null,
        public int|string|null $activatedDate = null,
    ) {
    }
}
