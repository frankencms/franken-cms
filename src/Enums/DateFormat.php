<?php

declare(strict_types=1);

namespace FrankenCms\Enums;

use DateTime;
use DateTimeInterface;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum DateFormat: string implements HasDescription, HasLabel
{
    case FULL_MONTH_DAY_YEAR = 'F j, Y'; // Example: January 1, 2025
    case YEAR_MONTH_DAY = 'Y-m-d';      // Example: 2025-01-01
    case MONTH_DAY_YEAR = 'm/d/Y';     // Example: 01/01/2025
    case DAY_MONTH_YEAR = 'd/m/Y';     // Example: 01/01/2025
    case CUSTOM = 'custom';

    /**
     * Get a formatted date string based on the selected date format.
     */
    public function formatDate(DateTimeInterface $date): string
    {
        return $date->format($this->value);
    }

    /**
     * Get a human-readable example of the date format using the current date.
     */
    public function getExample(): string
    {
        $currentDate = new DateTime;
        return $currentDate->format($this->value);
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FULL_MONTH_DAY_YEAR => 'Month Day, Year',
            self::YEAR_MONTH_DAY      => 'Year-Month-Day',
            self::MONTH_DAY_YEAR      => 'Month/Day/Year',
            self::DAY_MONTH_YEAR      => 'Day/Month/Year',
            self::CUSTOM              => 'Custom',
        };

    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::FULL_MONTH_DAY_YEAR => 'Example: January 1, 2025',
            self::YEAR_MONTH_DAY      => 'Example: 2025-01-01',
            self::MONTH_DAY_YEAR      => 'Example: 01/01/2025',
            self::DAY_MONTH_YEAR      => 'Example: 01/01/2025',
            self::CUSTOM              => 'Define a custom date format using PHP date format characters.',
        };

    }
}
