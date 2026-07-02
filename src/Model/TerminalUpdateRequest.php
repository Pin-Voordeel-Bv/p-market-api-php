<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalUpdateRequest
{
    public function __construct(
        public string $name,
        public ?string $resellerName = null,
        public ?string $modelName = null,
        public ?string $tid = null,
        public ?string $serialNo = null,
        public ?string $merchantName = null,
        public ?string $location = null,
        public ?string $remark = null,
    ) {
    }
}
