<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class PushRki2TerminalRequest
{
    public function __construct(
        public string $rkiKey,
        public ?string $tid = null,
        public ?string $serialNo = null,
        public int|string|null $effectiveTime = null,
        public int|string|null $expiredTime = null,
    ) {
    }
}
