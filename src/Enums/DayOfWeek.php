<?php

declare(strict_types=1);

namespace FrankenCms\Enums;

use Filament\Support\Contracts\HasLabel;

enum DayOfWeek: string implements HasLabel
{
    case SUNDAY = 'Sunday';
    case MONDAY = 'Monday';
    case TUESDAY = 'Tuesday';
    case WEDNESDAY = 'Wednesday';
    case THURSDAY = 'Thursday';
    case FRIDAY = 'Friday';
    case SATURDAY = 'Saturday';

    /**
     * Get the numeric representation of the day of the week (0 for Sunday, 6 for Saturday).
     */
    public function toNumeric(): int
    {
        return match ($this) {
            self::SUNDAY    => 0,
            self::MONDAY    => 1,
            self::TUESDAY   => 2,
            self::WEDNESDAY => 3,
            self::THURSDAY  => 4,
            self::FRIDAY    => 5,
            self::SATURDAY  => 6,
        };
    }

    /**
     * Check if the day is a weekday.
     */
    public function isWeekday(): bool
    {
        return $this !== self::SUNDAY && $this !== self::SATURDAY;
    }

    /**
     * Check if the day is a weekend.
     */
    public function isWeekend(): bool
    {
        return $this === self::SUNDAY || $this === self::SATURDAY;
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SUNDAY    => 'Sunday',
            self::MONDAY    => 'Monday',
            self::TUESDAY   => 'Tuesday',
            self::WEDNESDAY => 'Wednesday',
            self::THURSDAY  => 'Thursday',
            self::FRIDAY    => 'Friday',
            self::SATURDAY  => 'Saturday',
        };

    }
}
