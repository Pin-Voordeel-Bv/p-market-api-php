<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EntityAttributeLabelUpdateRequest
{
    /**
     * @param list<EntityAttributeLabelInfo|array{locale:string,label:string}> $entityAttributeLabelList
     */
    public function __construct(
        public array $entityAttributeLabelList,
    ) {
    }
}
