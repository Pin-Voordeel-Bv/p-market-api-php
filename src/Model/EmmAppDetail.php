<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class EmmAppDetail
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $name = null,
        public ?string $packageName = null,
        public ?string $iconUrl = null,
        public ?string $type = null,
        public ?string $developerName = null,
        public ?bool $supportManagedConfig = null,
        public ?int $minAndroidSdkVersion = null,
        public array $screenshotUrls = [],
        public int|string|null $updateTime = null,
        public ?string $appPricing = null,
        public ?string $fullDescription = null,
        public array $appVersions = [],
    ) {
    }
}
