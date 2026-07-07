<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalApkParam
{
    public function __construct(
        public ?string $paramTemplateName = null,
        public int|string|null $actionStatus = null,
        public ?int $errorCode = null,
        public array $configuredParameters = [],
    ) {
    }
}
