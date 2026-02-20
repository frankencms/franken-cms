<?php

declare(strict_types=1);

namespace FrankenCms\Enums;

use DateTime;
use DateTimeInterface;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Override;

enum TimeFormat: string implements HasDescription, HasLabel
{
    case HOURS_12_MINUTES_LOWERCASE = 'g:i a'; // Example: 2:30 pm
    case HOURS_12_MINUTES_UPPERCASE = 'g:i A'; // Example: 2:30 PM
    case HOURS_24_MINUTES = 'H:i';            // Example: 14:30

    case CUSTOM = 'custom';

    /**
     * Get a formatted time string based on the selected time format.
     */
    public function formatTime(DateTimeInterface $time): string
    {
        return $time->format($this->value);
    }

    /**
     * Get an example of the time format using the current time.
     */
    public function getExample(): string
    {
        $currentTime = new DateTime;
        return $currentTime->format($this->value);
    }

    #[Override]
    public function getLabel(): ?string
    {
        return match ($this) {
            self::HOURS_12_MINUTES_LOWERCASE => '12-hour (lowercase)',
            self::HOURS_12_MINUTES_UPPERCASE => '12-hour (uppercase)',
            self::HOURS_24_MINUTES           => '24-hour',
            self::CUSTOM                     => 'Custom',
        };

    }

    #[Override]
    public function getDescription(): ?string
    {

        return match ($this) {
            self::HOURS_12_MINUTES_LOWERCASE => 'Example: 2:30 pm',
            self::HOURS_12_MINUTES_UPPERCASE => 'Example: 2:30 PM',
            self::HOURS_24_MINUTES           => 'Example: 14:30',
            self::CUSTOM                     => 'Define a custom time format using PHP date format characters.',
        };
    }
}
