<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalLog
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $fileName = null,
        public ?string $logType = null,
        public ?string $status = null,
        public ?int $createdDate = null,
        public ?int $updatedDate = null,
        public ?int $expiredDate = null,
        public ?string $downloadUrl = null,
    ) {
    }
}
