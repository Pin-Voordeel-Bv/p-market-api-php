<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class CreateTerminalGroupRkiRequest
{
    public function __construct(
        public int|string $groupId,
        public string $rkiKey,
        public int|string|null $effectiveTime = null,
        public int|string|null $expiredTime = null,
    ) {
    }
}
