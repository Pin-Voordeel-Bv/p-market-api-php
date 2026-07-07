<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class OptimizedParameterPushHistory
{
    public function __construct(
        public int|string|null $terminalId = null,
        public ?string $serialNo = null,
        public ?string $appName = null,
        public ?string $versionName = null,
        public int|string|null $pushStartTime = null,
        public int|string|null $appPushTime = null,
        public ?string $appPushStatus = null,
        public ?string $appPushError = null,
        public ?string $parameterTemplateName = null,
        public int|string|null $parameterPushTime = null,
        public ?string $parameterPushStatus = null,
        public ?string $parameterPushError = null,
        public array|string|null $parameters = null,
        public ?string $pushType = null,
    ) {
    }
}
