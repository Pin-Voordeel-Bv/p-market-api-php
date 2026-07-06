<?php
declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalGroup
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $resellerName = null,
        public ?string $modelName = null,
        public ?string $name = null,
        public ?string $status = null,
        public ?string $description = null,
        public int|string|null $createdByResellerId = null,
        public int|string|null $createdDate = null,
        public int|string|null $updatedDate = null,
        public ?int $terminalCount = null,
        public ?bool $dynamic = null,
        public ?bool $containSubResellerTerminal = null,
        public array $merchantNames = [],
    ) {}
}
