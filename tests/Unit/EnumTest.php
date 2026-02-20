<?php

use FrankenCms\Enums\DateFormat;
use FrankenCms\Enums\DayOfWeek;
use FrankenCms\Enums\LinkTargets;
use FrankenCms\Enums\PermalinkStructure;
use FrankenCms\Enums\PermalinkTags;
use FrankenCms\Enums\PostStatus;
use FrankenCms\Enums\TimeFormat;

describe('DateFormat', function () {

    it('has correct cases', function () {
        expect(DateFormat::cases())->toHaveCount(5);
    });

    it('has correct backing values', function () {
        expect(DateFormat::FULL_MONTH_DAY_YEAR->value)->toBe('F j, Y');
        expect(DateFormat::YEAR_MONTH_DAY->value)->toBe('Y-m-d');
        expect(DateFormat::MONTH_DAY_YEAR->value)->toBe('m/d/Y');
        expect(DateFormat::DAY_MONTH_YEAR->value)->toBe('d/m/Y');
        expect(DateFormat::CUSTOM->value)->toBe('custom');
    });

    it('can be created from value', function () {
        expect(DateFormat::from('F j, Y'))->toBe(DateFormat::FULL_MONTH_DAY_YEAR);
        expect(DateFormat::from('Y-m-d'))->toBe(DateFormat::YEAR_MONTH_DAY);
        expect(DateFormat::from('m/d/Y'))->toBe(DateFormat::MONTH_DAY_YEAR);
        expect(DateFormat::from('d/m/Y'))->toBe(DateFormat::DAY_MONTH_YEAR);
        expect(DateFormat::from('custom'))->toBe(DateFormat::CUSTOM);
    });

    it('returns null for invalid value with tryFrom', function () {
        expect(DateFormat::tryFrom('invalid'))->toBeNull();
    });

    it('formats a date correctly', function () {
        $date = new DateTime('2025-03-15');

        expect(DateFormat::FULL_MONTH_DAY_YEAR->formatDate($date))->toBe('March 15, 2025');
        expect(DateFormat::YEAR_MONTH_DAY->formatDate($date))->toBe('2025-03-15');
        expect(DateFormat::MONTH_DAY_YEAR->formatDate($date))->toBe('03/15/2025');
        expect(DateFormat::DAY_MONTH_YEAR->formatDate($date))->toBe('15/03/2025');
    });

    it('returns an example string', function () {
        foreach (DateFormat::cases() as $format) {
            expect($format->getExample())->toBeString()->not->toBeEmpty();
        }
    });

    it('returns labels for all cases', function () {
        expect(DateFormat::FULL_MONTH_DAY_YEAR->getLabel())->toBe('Month Day, Year');
        expect(DateFormat::YEAR_MONTH_DAY->getLabel())->toBe('Year-Month-Day');
        expect(DateFormat::MONTH_DAY_YEAR->getLabel())->toBe('Month/Day/Year');
        expect(DateFormat::DAY_MONTH_YEAR->getLabel())->toBe('Day/Month/Year');
        expect(DateFormat::CUSTOM->getLabel())->toBe('Custom');
    });

    it('returns descriptions for all cases', function () {
        expect(DateFormat::FULL_MONTH_DAY_YEAR->getDescription())->toBe('Example: January 1, 2025');
        expect(DateFormat::YEAR_MONTH_DAY->getDescription())->toBe('Example: 2025-01-01');
        expect(DateFormat::MONTH_DAY_YEAR->getDescription())->toBe('Example: 01/01/2025');
        expect(DateFormat::DAY_MONTH_YEAR->getDescription())->toBe('Example: 01/01/2025');
        expect(DateFormat::CUSTOM->getDescription())->toContain('custom date format');
    });

    it('implements HasLabel and HasDescription', function () {
        foreach (DateFormat::cases() as $case) {
            expect($case)->toBeInstanceOf(\Filament\Support\Contracts\HasLabel::class);
            expect($case)->toBeInstanceOf(\Filament\Support\Contracts\HasDescription::class);
        }
    });
});

describe('DayOfWeek', function () {

    it('has all seven days', function () {
        expect(DayOfWeek::cases())->toHaveCount(7);
    });

    it('has correct backing values', function () {
        expect(DayOfWeek::SUNDAY->value)->toBe('Sunday');
        expect(DayOfWeek::MONDAY->value)->toBe('Monday');
        expect(DayOfWeek::TUESDAY->value)->toBe('Tuesday');
        expect(DayOfWeek::WEDNESDAY->value)->toBe('Wednesday');
        expect(DayOfWeek::THURSDAY->value)->toBe('Thursday');
        expect(DayOfWeek::FRIDAY->value)->toBe('Friday');
        expect(DayOfWeek::SATURDAY->value)->toBe('Saturday');
    });

    it('converts to correct numeric values', function () {
        expect(DayOfWeek::SUNDAY->toNumeric())->toBe(0);
        expect(DayOfWeek::MONDAY->toNumeric())->toBe(1);
        expect(DayOfWeek::TUESDAY->toNumeric())->toBe(2);
        expect(DayOfWeek::WEDNESDAY->toNumeric())->toBe(3);
        expect(DayOfWeek::THURSDAY->toNumeric())->toBe(4);
        expect(DayOfWeek::FRIDAY->toNumeric())->toBe(5);
        expect(DayOfWeek::SATURDAY->toNumeric())->toBe(6);
    });

    it('identifies weekdays correctly', function () {
        expect(DayOfWeek::MONDAY->isWeekday())->toBeTrue();
        expect(DayOfWeek::TUESDAY->isWeekday())->toBeTrue();
        expect(DayOfWeek::WEDNESDAY->isWeekday())->toBeTrue();
        expect(DayOfWeek::THURSDAY->isWeekday())->toBeTrue();
        expect(DayOfWeek::FRIDAY->isWeekday())->toBeTrue();
        expect(DayOfWeek::SATURDAY->isWeekday())->toBeFalse();
        expect(DayOfWeek::SUNDAY->isWeekday())->toBeFalse();
    });

    it('identifies weekends correctly', function () {
        expect(DayOfWeek::SATURDAY->isWeekend())->toBeTrue();
        expect(DayOfWeek::SUNDAY->isWeekend())->toBeTrue();
        expect(DayOfWeek::MONDAY->isWeekend())->toBeFalse();
        expect(DayOfWeek::TUESDAY->isWeekend())->toBeFalse();
        expect(DayOfWeek::WEDNESDAY->isWeekend())->toBeFalse();
        expect(DayOfWeek::THURSDAY->isWeekend())->toBeFalse();
        expect(DayOfWeek::FRIDAY->isWeekend())->toBeFalse();
    });

    it('isWeekday and isWeekend are mutually exclusive', function () {
        foreach (DayOfWeek::cases() as $day) {
            expect($day->isWeekday())->not->toBe($day->isWeekend());
        }
    });

    it('returns labels for all cases', function () {
        foreach (DayOfWeek::cases() as $day) {
            expect($day->getLabel())->toBe($day->value);
        }
    });

    it('implements HasLabel', function () {
        foreach (DayOfWeek::cases() as $case) {
            expect($case)->toBeInstanceOf(\Filament\Support\Contracts\HasLabel::class);
        }
    });
});

describe('LinkTargets', function () {

    it('has correct cases', function () {
        expect(LinkTargets::cases())->toHaveCount(4);
    });

    it('has correct backing values', function () {
        expect(LinkTargets::_SELF->value)->toBe('_self');
        expect(LinkTargets::_BLANK->value)->toBe('_blank');
        expect(LinkTargets::_PARENT->value)->toBe('_parent');
        expect(LinkTargets::_TOP->value)->toBe('_top');
    });

    it('can be created from value', function () {
        expect(LinkTargets::from('_self'))->toBe(LinkTargets::_SELF);
        expect(LinkTargets::from('_blank'))->toBe(LinkTargets::_BLANK);
        expect(LinkTargets::from('_parent'))->toBe(LinkTargets::_PARENT);
        expect(LinkTargets::from('_top'))->toBe(LinkTargets::_TOP);
    });

    it('returns labels for all cases', function () {
        expect(LinkTargets::_SELF->getLabel())->toBe('Same Window');
        expect(LinkTargets::_BLANK->getLabel())->toBe('New Window');
        expect(LinkTargets::_PARENT->getLabel())->toBe('Parent Frame');
        expect(LinkTargets::_TOP->getLabel())->toBe('Top Frame');
    });

    it('implements HasLabel', function () {
        foreach (LinkTargets::cases() as $case) {
            expect($case)->toBeInstanceOf(\Filament\Support\Contracts\HasLabel::class);
        }
    });
});

describe('PermalinkStructure', function () {

    it('has correct cases', function () {
        expect(PermalinkStructure::cases())->toHaveCount(5);
    });

    it('has correct backing values', function () {
        expect(PermalinkStructure::POST_NAME->value)->toBe('/%postname%/');
        expect(PermalinkStructure::DAY_AND_NAME->value)->toBe('/%year%/%monthnum%/%day%/%postname%/');
        expect(PermalinkStructure::MONTH_AND_NAME->value)->toBe('/%year%/%monthnum%/%postname%/');
        expect(PermalinkStructure::NUMERIC->value)->toBe('/%post_id%');
        expect(PermalinkStructure::CUSTOM->value)->toBe('custom');
    });

    it('returns permalink tags with descriptions', function () {
        $tags = PermalinkStructure::getPermalinkTags();

        expect($tags)->toBeArray();
        expect($tags)->toHaveKeys(['%year%', '%monthnum%', '%day%', '%hour%', '%minute%', '%second%', '%post_id%', '%postname%', '%category%', '%author%']);

        foreach ($tags as $tag => $description) {
            expect($tag)->toStartWith('%')->toEndWith('%');
            expect($description)->toBeString()->not->toBeEmpty();
        }
    });

    it('returns example URLs for all cases', function () {
        expect(PermalinkStructure::POST_NAME->getExample())->toBe('/sample-post/');
        expect(PermalinkStructure::DAY_AND_NAME->getExample())->toBe('/2025/01/26/sample-post/');
        expect(PermalinkStructure::MONTH_AND_NAME->getExample())->toBe('/2025/01/sample-post/');
        expect(PermalinkStructure::NUMERIC->getExample())->toBe('/123');
        expect(PermalinkStructure::CUSTOM->getExample())->toBe('/your-custom-structure/');
    });

    it('returns labels for all cases', function () {
        expect(PermalinkStructure::POST_NAME->getLabel())->toBe('Post Name');
        expect(PermalinkStructure::DAY_AND_NAME->getLabel())->toBe('Day and Name');
        expect(PermalinkStructure::MONTH_AND_NAME->getLabel())->toBe('Month and Name');
        expect(PermalinkStructure::NUMERIC->getLabel())->toBe('Numeric');
        expect(PermalinkStructure::CUSTOM->getLabel())->toBe('Custom');
    });

    it('returns descriptions for all cases', function () {
        foreach (PermalinkStructure::cases() as $case) {
            expect($case->getDescription())->toBeString()->not->toBeEmpty();
        }
    });

    it('non-custom descriptions contain URLs', function () {
        expect(PermalinkStructure::POST_NAME->getDescription())->toContain('sample-post');
        expect(PermalinkStructure::DAY_AND_NAME->getDescription())->toContain('2025/01/26/sample-post');
        expect(PermalinkStructure::MONTH_AND_NAME->getDescription())->toContain('2025/01/sample-post');
        expect(PermalinkStructure::NUMERIC->getDescription())->toContain('/123');
    });

    it('implements HasLabel and HasDescription', function () {
        foreach (PermalinkStructure::cases() as $case) {
            expect($case)->toBeInstanceOf(\Filament\Support\Contracts\HasLabel::class);
            expect($case)->toBeInstanceOf(\Filament\Support\Contracts\HasDescription::class);
        }
    });
});

describe('PermalinkTags', function () {

    it('has correct cases', function () {
        expect(PermalinkTags::cases())->toHaveCount(10);
    });

    it('has correct backing values', function () {
        expect(PermalinkTags::YEAR->value)->toBe('%year%');
        expect(PermalinkTags::MONTHNUM->value)->toBe('%monthnum%');
        expect(PermalinkTags::DAY->value)->toBe('%day%');
        expect(PermalinkTags::HOUR->value)->toBe('%hour%');
        expect(PermalinkTags::MINUTE->value)->toBe('%minute%');
        expect(PermalinkTags::SECOND->value)->toBe('%second%');
        expect(PermalinkTags::POST_ID->value)->toBe('%post_id%');
        expect(PermalinkTags::POSTNAME->value)->toBe('%postname%');
        expect(PermalinkTags::CATEGORY->value)->toBe('%category%');
        expect(PermalinkTags::AUTHOR->value)->toBe('%author%');
    });

    it('all backing values are wrapped in percent signs', function () {
        foreach (PermalinkTags::cases() as $tag) {
            expect($tag->value)->toStartWith('%')->toEndWith('%');
        }
    });

    it('labels match backing values', function () {
        foreach (PermalinkTags::cases() as $tag) {
            expect($tag->getLabel())->toBe($tag->value);
        }
    });

    it('returns descriptions for all cases', function () {
        foreach (PermalinkTags::cases() as $tag) {
            expect($tag->getDescription())->toBeString()->not->toBeEmpty();
        }
    });

    it('descriptions contain example values', function () {
        expect(PermalinkTags::YEAR->getDescription())->toContain('2025');
        expect(PermalinkTags::MONTHNUM->getDescription())->toContain('01');
        expect(PermalinkTags::DAY->getDescription())->toContain('26');
        expect(PermalinkTags::HOUR->getDescription())->toContain('14');
        expect(PermalinkTags::MINUTE->getDescription())->toContain('30');
        expect(PermalinkTags::SECOND->getDescription())->toContain('45');
        expect(PermalinkTags::POST_ID->getDescription())->toContain('123');
        expect(PermalinkTags::POSTNAME->getDescription())->toContain('sample-post');
        expect(PermalinkTags::CATEGORY->getDescription())->toContain('news');
        expect(PermalinkTags::AUTHOR->getDescription())->toContain('admin');
    });

    it('implements HasLabel and HasDescription', function () {
        foreach (PermalinkTags::cases() as $case) {
            expect($case)->toBeInstanceOf(\Filament\Support\Contracts\HasLabel::class);
            expect($case)->toBeInstanceOf(\Filament\Support\Contracts\HasDescription::class);
        }
    });
});

describe('PostStatus', function () {

    it('has correct cases', function () {
        expect(PostStatus::cases())->toHaveCount(5);
    });

    it('has correct backing values', function () {
        expect(PostStatus::DRAFT->value)->toBe('draft');
        expect(PostStatus::PENDING->value)->toBe('pending');
        expect(PostStatus::PRIVATE->value)->toBe('private');
        expect(PostStatus::SCHEDULED->value)->toBe('scheduled');
        expect(PostStatus::PUBLISH->value)->toBe('published');
    });

    it('can be created from value', function () {
        expect(PostStatus::from('draft'))->toBe(PostStatus::DRAFT);
        expect(PostStatus::from('pending'))->toBe(PostStatus::PENDING);
        expect(PostStatus::from('private'))->toBe(PostStatus::PRIVATE);
        expect(PostStatus::from('scheduled'))->toBe(PostStatus::SCHEDULED);
        expect(PostStatus::from('published'))->toBe(PostStatus::PUBLISH);
    });

    it('returns labels for all cases', function () {
        expect(PostStatus::DRAFT->getLabel())->toBe('Draft');
        expect(PostStatus::PENDING->getLabel())->toBe('Pending');
        expect(PostStatus::PRIVATE->getLabel())->toBe('Private');
        expect(PostStatus::SCHEDULED->getLabel())->toBe('Scheduled');
        expect(PostStatus::PUBLISH->getLabel())->toBe('Published');
    });

    it('returns descriptions for all cases', function () {
        expect(PostStatus::DRAFT->getDescription())->toBe('Not ready to publish.');
        expect(PostStatus::PENDING->getDescription())->toBe('Waiting for review before publishing.');
        expect(PostStatus::PRIVATE->getDescription())->toBe('Only visible to administrators and editors.');
        expect(PostStatus::SCHEDULED->getDescription())->toBe('Published automatically at a future date.');
        expect(PostStatus::PUBLISH->getDescription())->toBe('Visible to everyone.');
    });

    it('returns icons for all cases', function () {
        expect(PostStatus::DRAFT->getIcon())->toBe('heroicon-o-pencil');
        expect(PostStatus::PENDING->getIcon())->toBe('heroicon-o-clock');
        expect(PostStatus::PRIVATE->getIcon())->toBe('heroicon-o-eye-off');
        expect(PostStatus::SCHEDULED->getIcon())->toBe('heroicon-o-calendar');
        expect(PostStatus::PUBLISH->getIcon())->toBe('heroicon-o-check-circle');
    });

    it('all icons are heroicons', function () {
        foreach (PostStatus::cases() as $status) {
            expect($status->getIcon())->toStartWith('heroicon-');
        }
    });

    it('implements HasLabel, HasDescription, and HasIcon', function () {
        foreach (PostStatus::cases() as $case) {
            expect($case)->toBeInstanceOf(\Filament\Support\Contracts\HasLabel::class);
            expect($case)->toBeInstanceOf(\Filament\Support\Contracts\HasDescription::class);
            expect($case)->toBeInstanceOf(\Filament\Support\Contracts\HasIcon::class);
        }
    });
});

describe('TimeFormat', function () {

    it('has correct cases', function () {
        expect(TimeFormat::cases())->toHaveCount(4);
    });

    it('has correct backing values', function () {
        expect(TimeFormat::HOURS_12_MINUTES_LOWERCASE->value)->toBe('g:i a');
        expect(TimeFormat::HOURS_12_MINUTES_UPPERCASE->value)->toBe('g:i A');
        expect(TimeFormat::HOURS_24_MINUTES->value)->toBe('H:i');
        expect(TimeFormat::CUSTOM->value)->toBe('custom');
    });

    it('formats a time correctly', function () {
        $time = new DateTime('2025-01-01 14:30:00');

        expect(TimeFormat::HOURS_12_MINUTES_LOWERCASE->formatTime($time))->toBe('2:30 pm');
        expect(TimeFormat::HOURS_12_MINUTES_UPPERCASE->formatTime($time))->toBe('2:30 PM');
        expect(TimeFormat::HOURS_24_MINUTES->formatTime($time))->toBe('14:30');
    });

    it('returns an example string', function () {
        foreach (TimeFormat::cases() as $format) {
            expect($format->getExample())->toBeString()->not->toBeEmpty();
        }
    });

    it('returns labels for all cases', function () {
        expect(TimeFormat::HOURS_12_MINUTES_LOWERCASE->getLabel())->toBe('12-hour (lowercase)');
        expect(TimeFormat::HOURS_12_MINUTES_UPPERCASE->getLabel())->toBe('12-hour (uppercase)');
        expect(TimeFormat::HOURS_24_MINUTES->getLabel())->toBe('24-hour');
        expect(TimeFormat::CUSTOM->getLabel())->toBe('Custom');
    });

    it('returns descriptions for all cases', function () {
        expect(TimeFormat::HOURS_12_MINUTES_LOWERCASE->getDescription())->toBe('Example: 2:30 pm');
        expect(TimeFormat::HOURS_12_MINUTES_UPPERCASE->getDescription())->toBe('Example: 2:30 PM');
        expect(TimeFormat::HOURS_24_MINUTES->getDescription())->toBe('Example: 14:30');
        expect(TimeFormat::CUSTOM->getDescription())->toContain('custom time format');
    });

    it('implements HasLabel and HasDescription', function () {
        foreach (TimeFormat::cases() as $case) {
            expect($case)->toBeInstanceOf(\Filament\Support\Contracts\HasLabel::class);
            expect($case)->toBeInstanceOf(\Filament\Support\Contracts\HasDescription::class);
        }
    });
});
