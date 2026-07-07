<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class UpdateApkParameterRequest
{
    public function __construct(
        public ?string $paramTemplateName = null,
        public array $parameters = [],
        public array $base64FileParameters = [],
        public ?bool $validateUndefinedParameter = null,
    ) {
    }
}
