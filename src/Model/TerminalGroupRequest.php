<?php
declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalGroupRequest
{
    public function __construct(
        public ?string $name = null,
        public ?string $modelName = null,
        public ?string $resellerName = null,
        public ?string $description = null,
        public ?string $status = null,
        public ?bool $dynamic = null,
        public ?bool $containSubResellerTerminal = null,
        public array $merchantNameList = [],
    ) {}
}
