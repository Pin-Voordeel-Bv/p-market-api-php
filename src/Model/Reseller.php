<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class Reseller
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $country = null,
        public ?string $postcode = null,
        public ?string $address = null,
        public ?string $company = null,
        public ?string $contact = null,
        public ?string $email = null,
        public ?string $status = null,
        public SimpleReseller|array|null $parent = null,
        public array $entityAttributeValues = [],
    ) {
    }
}
