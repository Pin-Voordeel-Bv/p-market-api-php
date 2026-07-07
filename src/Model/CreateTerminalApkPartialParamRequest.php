<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class CreateTerminalApkPartialParamRequest
{
    public function __construct(
        public string $packageName,
        public array $parameters,
        public string $partialPid,
        public ?string $tid = null,
        public ?string $serialNo = null,
        public ?string $version = null,
        public ?string $templateName = null,
        public array $base64FileParameters = [],
        public ?string $pushTemplateName = null,
        public ?bool $inheritPushHistory = null,
        public ?bool $forceUpdate = null,
        public ?bool $wifiOnly = null,
        public ?string $effectiveTime = null,
        public ?string $expiredTime = null,
        public ?bool $validateUndefinedParameter = null,
    ) {
    }
}
