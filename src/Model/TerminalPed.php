<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalPed
{
    public function __construct(
        public ?string $info = null,
    ) {
    }
}
