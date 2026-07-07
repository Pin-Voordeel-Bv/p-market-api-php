<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final readonly class ApkParameter
{
    public function __construct(
        public int|string|null $id = null,
        public ?string $name = null,
        public ?string $paramTemplateName = null,
        public int|string|null $createdDate = null,
        public int|string|null $updatedDate = null,
        public Apk|array|null $apk = null,
        public ?bool $apkAvailable = null,
    ) {
    }
}
