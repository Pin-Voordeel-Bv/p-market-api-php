<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class MerchantCreateRequest
{
    /**
     * @param list<string> $merchantCategoryNames
     * @param array<string, string> $entityAttributeValues
     */
    public function __construct(
        public string $name,
        public string $resellerName,
        public ?string $email = null,
        public ?string $contact = null,
        public ?string $country = null,
        public ?string $phone = null,
        public ?string $province = null,
        public ?string $city = null,
        public ?string $postcode = null,
        public ?string $address = null,
        public ?string $description = null,
        public bool $createUserFlag = false,
        public array $merchantCategoryNames = [],
        public array $entityAttributeValues = [],
        public bool $activateWhenCreate = false,
    ) {
    }
}
