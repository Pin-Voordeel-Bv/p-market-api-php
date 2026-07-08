<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class DisablePushRkiTaskRequest
{
    public function __construct(
        public string $rkiKey,
        public ?string $tid = null,
        public ?string $serialNo = null,
    ) {
    }
}
