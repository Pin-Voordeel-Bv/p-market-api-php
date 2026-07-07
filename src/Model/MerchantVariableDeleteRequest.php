<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class MerchantVariableDeleteRequest
{
    /**
     * @param array<int|string> $variableIds
     */
    public function __construct(
        public array $variableIds,
    ) {
    }
}
