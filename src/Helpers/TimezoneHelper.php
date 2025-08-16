<?php

declare(strict_types=1);

namespace FrankenCms\Helpers;

use DateTimeZone;

final class TimezoneHelper
{
    /**
     * Get all timezones grouped by region, including manual UTC offsets.
     */
    public static function getGroupedTimezones(): array
    {
        // Get all timezone identifiers
        $timezones = DateTimeZone::listIdentifiers();

        // Initialize an empty array for grouping
        $groupedTimezones = [];

        // Loop through each timezone and group by the first part (region)
        foreach ($timezones as $timezone) {
            // Split the timezone into region and city
            $parts = explode('/', $timezone, 2);
            $region = $parts[0];

            // Initialize the region group if not already present
            if (! isset($groupedTimezones[$region])) {
                $groupedTimezones[$region] = [];
            }

            // Add the timezone to the region group
            $groupedTimezones[$region][$timezone] = $timezone;
        }

        // Define manual UTC offsets
        $utcOffsets = [
            'UTC-12'    => 'UTC-12',
            'UTC-11.5'  => 'UTC-11:30',
            'UTC-11'    => 'UTC-11',
            'UTC-10.5'  => 'UTC-10:30',
            'UTC-10'    => 'UTC-10',
            'UTC-9.5'   => 'UTC-9:30',
            'UTC-9'     => 'UTC-9',
            'UTC-8.5'   => 'UTC-8:30',
            'UTC-8'     => 'UTC-8',
            'UTC-7.5'   => 'UTC-7:30',
            'UTC-7'     => 'UTC-7',
            'UTC-6.5'   => 'UTC-6:30',
            'UTC-6'     => 'UTC-6',
            'UTC-5.5'   => 'UTC-5:30',
            'UTC-5'     => 'UTC-5',
            'UTC-4.5'   => 'UTC-4:30',
            'UTC-4'     => 'UTC-4',
            'UTC-3.5'   => 'UTC-3:30',
            'UTC-3'     => 'UTC-3',
            'UTC-2.5'   => 'UTC-2:30',
            'UTC-2'     => 'UTC-2',
            'UTC-1.5'   => 'UTC-1:30',
            'UTC-1'     => 'UTC-1',
            'UTC-0.5'   => 'UTC-0:30',
            'UTC+0'     => 'UTC+0',
            'UTC+0.5'   => 'UTC+0:30',
            'UTC+1'     => 'UTC+1',
            'UTC+1.5'   => 'UTC+1:30',
            'UTC+2'     => 'UTC+2',
            'UTC+2.5'   => 'UTC+2:30',
            'UTC+3'     => 'UTC+3',
            'UTC+3.5'   => 'UTC+3:30',
            'UTC+4'     => 'UTC+4',
            'UTC+4.5'   => 'UTC+4:30',
            'UTC+5'     => 'UTC+5',
            'UTC+5.5'   => 'UTC+5:30',
            'UTC+5.75'  => 'UTC+5:45',
            'UTC+6'     => 'UTC+6',
            'UTC+6.5'   => 'UTC+6:30',
            'UTC+7'     => 'UTC+7',
            'UTC+7.5'   => 'UTC+7:30',
            'UTC+8'     => 'UTC+8',
            'UTC+8.5'   => 'UTC+8:30',
            'UTC+8.75'  => 'UTC+8:45',
            'UTC+9'     => 'UTC+9',
            'UTC+9.5'   => 'UTC+9:30',
            'UTC+10'    => 'UTC+10',
            'UTC+10.5'  => 'UTC+10:30',
            'UTC+11'    => 'UTC+11',
            'UTC+11.5'  => 'UTC+11:30',
            'UTC+12'    => 'UTC+12',
            'UTC+12.75' => 'UTC+12:45',
            'UTC+13'    => 'UTC+13',
            'UTC+13.75' => 'UTC+13:45',
            'UTC+14'    => 'UTC+14',
        ];

        // Add UTC manual offsets
        $groupedTimezones['Manual UTC Offsets'] = $utcOffsets;

        // Return the grouped timezones
        return $groupedTimezones;
    }
}
