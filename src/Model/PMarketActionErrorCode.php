<?php

declare(strict_types=1);

namespace PinVandaag\PMarketAPI\Model;

final class PMarketActionErrorCode
{
    public static function labels(): array
    {
        return [
            0 => 'Default Value',
            1 => 'Download Failed',
            2 => 'Install Failed',
            3 => 'App Exist',
            4 => 'Application Out Of Date',
            5 => 'Application Param Duplicate',
            6 => 'Application Not Exist',
            7 => 'Application Version Mismatch',
            8 => 'Uninstall Error',
            9 => 'Task Expired',
            10 => 'MD5 Validation Error',
            11 => 'Uninstall Local Higher Version Failed',
            12 => 'Task Disabled',
            13 => 'Push Duplicate',
            14 => 'Invalid File Status',
            17 => 'Model Mismatch',
            18 => 'Reseller Mismatch',
            19 => 'File Already Installed',
            20 => 'File Version Too Low',
            21 => 'Set PED Failed',
            22 => 'File Deleted By User',
            23 => 'Invalid Parameter Variable',
            24 => 'AIP ID Not Defined',
            25 => 'Firmware Resource Mismatch',
            26 => 'Task Invalid',
            27 => 'Unable To Bind Terminal RKI Key',
            28 => 'Parameter File Parse Failed',
            29 => 'Terminal Removed From Group',
            30 => 'Task Deleted',
            31 => 'Application Deleted',
            32 => 'Firmware Deleted',
            33 => 'Terminal Group Deleted',
            34 => 'Terminal Deleted',
            35 => 'Param Task Cancelled As App Push Failed',
            36 => 'Application can not be uninstalled',
            37 => 'Filter failed',
        ];
    }
}
