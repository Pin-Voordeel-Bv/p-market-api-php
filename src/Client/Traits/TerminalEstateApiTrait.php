<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;

trait TerminalEstateApiTrait
{
    public function verifyTerminalEstate(string $serialNo): bool
    {
        $serialNo = trim($serialNo);

        if ($serialNo === '') {
            throw new PMarketAPIException('Parameter serialNo cannot be null and cannot be less than 1!');
        }

        $this->emptyResult(
            method: 'GET',
            endpoint: sprintf('/v1/3rdsys/estates/verify/%s', rawurlencode($serialNo)),
            actionDescription: sprintf('verify P Market terminal estate "%s"', $serialNo),
        );

        return true;
    }
}
