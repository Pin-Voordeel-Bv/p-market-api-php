<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EntityAttributeUpdateRequest
{
    public function __construct(
        public string $inputType,
        public bool $required,
        public string $defaultLabel,
        public ?int $minLength = null,
        public ?int $maxLength = null,
        public ?string $selector = null,
    ) {
    }
}
