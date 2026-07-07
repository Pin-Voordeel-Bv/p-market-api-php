<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class ApkFile
{
    public function __construct(
        public ?string $permissions = null,
        public ?string $paxPermission = null,
    ) {
    }
}
