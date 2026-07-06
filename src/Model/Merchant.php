<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class Merchant
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $name = null,
        public SimpleReseller|array|null $reseller = null,
        public ?string $country = null,
        public ?string $province = null,
        public ?string $city = null,
        public ?string $postcode = null,
        public ?string $address = null,
        public ?string $contact = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $status = null,
        public ?string $description = null,
        /**
         * @param array<string, string> $entityAttributeValues
         * @param list<MerchantCategory|array> $merchantCategory
         */
        public array $entityAttributeValues = [],
        public array $merchantCategory = [],
    ) {
    }
}
