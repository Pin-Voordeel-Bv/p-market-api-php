<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class ResellerUpdateRequest
{
    public function __construct(
        public string $name,
        public string $country,
        public string $contact,
        public string $phone,
        public ?string $email = null,
        public ?string $postcode = null,
        public ?string $address = null,
        public ?string $company = null,
        public ?string $parentResellerName = null,
        public array $entityAttributeValues = [],
    ) {
    }
}
