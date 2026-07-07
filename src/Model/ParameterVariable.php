<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class ParameterVariable
{
    public function __construct(
        public string $key,
        public ?string $packageName = null,
        public ?string $type = null,
        public ?string $value = null,
        public ?string $remarks = null,
    ) {
    }
}
