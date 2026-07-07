<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class CreateApkParameterRequest
{
    public function __construct(
        public string $packageName,
        public string $version,
        public string $name,
        public ?string $paramTemplateName = null,
        public array $parameters = [],
        public array $base64FileParameters = [],
        public ?bool $validateUndefinedParameter = null,
    ) {
    }
}
