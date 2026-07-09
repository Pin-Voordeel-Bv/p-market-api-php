<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmZteQuickUploadRecordCreateRequest
{
    public function __construct(
        public string $resellerName,
        public string $merchantName,
        public string $identifierType,
        public string $numbers,
        public ?string $manufacturer = null,
        public ?string $model = null,
    ) {
    }
}
