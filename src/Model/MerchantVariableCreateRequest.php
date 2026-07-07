<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class MerchantVariableCreateRequest
{
    /**
     * @param ParameterVariable[] $variableList
     */
    public function __construct(
        public int|string $merchantId,
        public array $variableList,
    ) {
    }
}
