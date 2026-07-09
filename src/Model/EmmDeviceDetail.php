<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmDeviceDetail
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $name = null,
        public ?string $serialNo = null,
        public ?string $type = null,
        public ?string $status = null,
        public ?string $securityStatus = null,
        public int|string|null $registerTime = null,
        public ?string $imei = null,
        public EmmModel|array|null $model = null,
        public EmmReseller|array|null $reseller = null,
        public EmmMerchant|array|null $merchant = null,
    ) {
    }
}
