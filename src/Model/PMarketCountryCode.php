<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final class PMarketCountryCode
{
    public const CN = 'CN';
    public const ITA = 'ITA';
    public const USA = 'USA';
    public const IRN = 'IRN';
    public const BRA = 'BRA';
    public const GBR = 'GBR';
    public const AUS = 'AUS';
    public const MYS = 'MYS';
    public const JPN = 'JPN';
    public const IND = 'IND';

    public static function all(): array
    {
        return [
            self::CN,
            self::ITA,
            self::USA,
            self::IRN,
            self::BRA,
            self::GBR,
            self::AUS,
            self::MYS,
            self::JPN,
            self::IND,
        ];
    }

    public static function isValid(string $countryCode): bool
    {
        return in_array(strtoupper(trim($countryCode)), self::all(), true);
    }
}
