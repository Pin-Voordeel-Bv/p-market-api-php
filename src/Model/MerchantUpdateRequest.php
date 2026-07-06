<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class MerchantUpdateRequest
{
    /**
     * @param list<string> $merchantCategoryNames
     * @param array<string, string> $entityAttributeValues
     */
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $resellerName = null,
        public ?string $contact = null,
        public ?string $country = null,
        public ?string $phone = null,
        public ?string $province = null,
        public ?string $city = null,
        public ?string $postcode = null,
        public ?string $address = null,
        public ?string $description = null,
        public ?bool $createUserFlag = null,
        public array $merchantCategoryNames = [],
        public array $entityAttributeValues = [],
    ) {
    }
}
