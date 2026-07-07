<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class TerminalParameterVariableRequest
{
    public function __construct(
        public ?string $tid = null,
        public ?string $serialNo = null,

        /** @var ParameterVariable[] */
        public array $variableList = [],
    ) {
    }
}
