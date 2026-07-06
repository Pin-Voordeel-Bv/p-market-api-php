<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EntityAttribute
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $entityType = null,
        public ?string $inputType = null,
        public ?int $minLength = null,
        public ?int $maxLength = null,
        public ?bool $required = null,
        public ?string $selector = null,
        public ?string $key = null,
        public ?int $index = null,
        public ?string $defaultLabel = null,
        public array $entityAttributeLabelList = [],
    ) {
    }
}
