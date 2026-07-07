<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class FileParameter
{
    public function __construct(
        public ?string $pid = null,
        public ?string $fileName = null,
        public ?string $fileData = null,
    ) {
    }
}
