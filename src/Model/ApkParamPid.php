<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class ApkParamPid
{
    public function __construct(
        public array $pidList = [],
    ) {
    }
}
