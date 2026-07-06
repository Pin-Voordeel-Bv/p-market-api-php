<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalLogDownloadTask
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $status = null,
        public ?string $downloadUrl = null,
        public ?int $expiredDate = null,
    ) {
    }
}
