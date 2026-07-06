<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EntityAttributeCreateRequest
{
    public function __construct(
        public string $entityType,
        public string $inputType,
        public bool $required,
        public string $key,
        public string $defaultLabel,
        public ?int $minLength = null,
        public ?int $maxLength = null,
        public ?string $selector = null,
    ) {
    }
}
