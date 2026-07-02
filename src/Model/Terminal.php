<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class Terminal
{
    /**
     * @param list<TerminalAttribute>|null $attributes
     */
    public function __construct(
        public ?string $id = null,
        public ?string $tid = null,
        public ?string $serialNo = null,
    ) {
    }
}
