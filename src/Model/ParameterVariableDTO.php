<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class ParameterVariableDTO
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $appPackageName = null,
        public ?string $appName = null,
        public ?string $type = null,
        public ?string $key = null,
        public ?string $value = null,
        public ?string $remarks = null,
        public ?string $source = null,
        public int|string|null $createdDate = null,
        public int|string|null $updatedDate = null,
    ) {
    }
}
