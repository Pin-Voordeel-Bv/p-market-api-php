<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Client\Traits;

use PinVandaag\PMarketAPI\Exception\PMarketAPIException;
use PinVandaag\PMarketAPI\Model\GoInsightCustomFilter;
use PinVandaag\PMarketAPI\Model\GoInsightDataQueryResult;

trait GoInsightApiTrait
{
    public function findDataFromInsight(
        string $queryCode,
        ?string $rangeType = null,
        array $customFilterList = [],
        ?int $pageNo = null,
        ?int $pageSize = null,
        string $timeZone = 'UTC',
    ): GoInsightDataQueryResult {
        $queryCode = trim($queryCode);

        if ($queryCode === '') {
            throw new PMarketAPIException('Parameter queryCode cannot be null');
        }

        if (strlen($queryCode) !== 8) {
            throw new PMarketAPIException('Parameter queryCode length must is 8');
        }

        if ($pageSize !== null && ($pageSize < 1 || $pageSize > 1000)) {
            throw new PMarketAPIException('Parameter pageSize must be range is 1 to 1000');
        }

        if ($pageNo !== null && $pageNo < 1) {
            throw new PMarketAPIException('Invalid pageNo');
        }

        $payload  = [];

        if ($rangeType !== null && $rangeType !== '') {
            $payload ['timeRangeType'] = $this->normalizeGoInsightRangeType($rangeType);
        }

        if ($customFilterList !== []) {
            $payload ['customFilterList'] = $this->goInsightCustomFilterListPayload($customFilterList);
        }

        if ($pageNo !== null && $pageSize !== null) {
            $payload ['pageNo'] = $pageNo;
            $payload ['pageSize'] = $pageSize;
        }

        // The Java SDK always sends a JSON body. When there are no parameters,
        // send {} instead of no body.
        $body = $payload !== [] ? $payload : new \stdClass();

        return $this->postResultData(
            endpoint: sprintf('/v1/3rdsys/goInsight/data/app-biz/%s', rawurlencode($queryCode)),
            responseClass: GoInsightDataQueryResult::class,
            actionDescription: sprintf('find P Market GoInsight data "%s"', $queryCode),
            headers: [
                'Content-Type' => 'application/json',
                'Time-Zone' => $timeZone,
            ],
            body: $body,
        );
    }

    private function goInsightCustomFilterListPayload(array $customFilterList): array
    {
        $payload = [];

        foreach ($customFilterList as $filter) {
            if ($filter instanceof GoInsightCustomFilter) {
                $cloName = $filter->cloName;
                $filterValue = $filter->filterValue;
            } elseif (is_array($filter)) {
                $cloName = (string) ($filter['cloName'] ?? $filter['colName'] ?? '');
                $filterValue = (string) ($filter['filterValue'] ?? '');
            } else {
                throw new PMarketAPIException('customFilterList must contain GoInsightCustomFilter objects or arrays.');
            }

            if ($cloName === '') {
                throw new PMarketAPIException('customFilter cloName cannot be empty.');
            }

            if ($filterValue !== '' && count(explode(',', $filterValue)) > 100) {
                throw new PMarketAPIException(sprintf('The %s filter value size can not over 100', $cloName));
            }

            $payload[] = [
                'cloName' => $this->normalizeGoInsightCustomColName($cloName),
                'filterValue' => $filterValue,
            ];
        }

        return $payload;
    }

    private function normalizeGoInsightCustomColName(string $cloName): string
    {
        return match ($cloName) {
            'RESELLER', 'Reseller', 'reseller' => 'reseller',
            'MERCHANT', 'Merchant', 'merchant' => 'merchant',
            'TERMINAL', 'Terminal', 'terminal' => 'terminal',
            'FACTORY', 'Factory', 'factory' => 'factory',
            'MODEL', 'Model', 'model' => 'model',
            default => $cloName,
        };
    }

    private function normalizeGoInsightRangeType(string $rangeType): string
    {
        return match ($rangeType) {
            'LAST_HOUR' => 'p1h',
            'YESTERDAY' => 'p1d',
            'LAST_WEEK' => 'p1w',
            'LAST_MONTH' => 'p1m',
            'LAST_QUARTER' => 'p1q',
            'LAST_YEAR' => 'p1y',

            'RECENT_5_MIN' => 'r5min',
            'RECENT_30_MIN' => 'r30min',
            'RECENT_HOUR' => 'r1h',
            'RECENT_3_HOUR' => 'r3h',
            'RECENT_DAY' => 'r1d',
            'RECENT_2_DAY' => 'r2d',
            'RECENT_5_DAY' => 'r5d',
            'RECENT_WEEK' => 'r1w',
            'RECENT_MONTH' => 'r1m',
            'RECENT_3_MONTH' => 'r3m',
            'RECENT_3_MONTH_BY_WEEK' => 'r3mbw',
            'RECENT_6_MONTH' => 'r6m',
            'RECENT_YEAR' => 'r1y',

            'THIS_HOUR' => 't1h',
            'TODAY' => 't1d',
            'THIS_WEEK' => 't1w',
            'THIS_MONTH' => 't1m',
            'THIS_QUARTER' => 't1q',
            'THIS_YEAR' => 't1y',

            'p1h','p1d','p1w','p1m','p1q','p1y',
            'r5min','r30min','r1h','r3h','r1d','r2d','r5d','r1w','r1m','r3m','r3mbw','r6m','r1y',
            't1h','t1d','t1w','t1m','t1q','t1y' => $rangeType,

            default => throw new PMarketAPIException('Invalid GoInsight rangeType.'),
        };
    }
}
