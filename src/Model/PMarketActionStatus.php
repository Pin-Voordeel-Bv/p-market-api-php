<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final class PMarketActionStatus
{
    public const NOT_STARTED = 0;
    public const STARTED = 1;
    public const SUCCESS = 2;
    public const FAILED = 3;
    public const WAITING = 4;

    public static function labels(): array
    {
        return [
            self::NOT_STARTED => 'Push task not started',
            self::STARTED => 'Push task started',
            self::SUCCESS => 'Push task success',
            self::FAILED => 'Push task fail',
            self::WAITING => 'Push task is waiting, no need push',
        ];
    }
}
