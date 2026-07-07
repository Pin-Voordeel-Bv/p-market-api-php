<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class CreateTerminalGroupApkRequest
{
    public function __construct(
        public int|string $groupId,
        public string $packageName,
        public ?string $pushTemplateName = null,
        public ?string $version = null,
        public ?string $templateName = null,
        public array $parameters = [],
        public array $base64FileParameters = [],
        public ?bool $inheritPushHistory = null,
        public ?bool $forceUpdate = null,
        public ?bool $wifiOnly = null,
        public ?string $effectiveTime = null,
        public ?string $expiredTime = null,
        public ?bool $validateUndefinedParameter = null,
        public ?bool $launcher = null,
    ) {
    }
}
