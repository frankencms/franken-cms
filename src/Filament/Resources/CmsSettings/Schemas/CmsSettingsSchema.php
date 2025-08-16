<?php

namespace FrankenCms\Filament\Resources\CmsSettings\Schemas;

use BackedEnum;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use FrankenCms\Enums\DateFormat;
use FrankenCms\Enums\DayOfWeek;
use FrankenCms\Enums\PermalinkStructure;
use FrankenCms\Enums\PermalinkTags;
use FrankenCms\Enums\TimeFormat;
use FrankenCms\Enums\UserRole;
use FrankenCms\Helpers\TimezoneHelper;
use FrankenCms\Models\Page;
use FrankenCms\Rules\PermalinkContainsPostPlaceholder;
use Illuminate\Support\HtmlString;

class CmsSettingsSchema
{
    public static function make(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Tabs')
                ->persistTabInQueryString('settings-tab')
                ->columnSpanFull()
                ->tabs([
                    Tab::make(__('franken-cms::messages.settings.general.title'))
                        ->schema([
                            Section::make(__('franken-cms::messages.settings.general.title'))
                                ->description(__('franken-cms::messages.settings.general.description'))
                                ->columns(3)
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('title')
                                        ->inlineLabel()
                                        ->required()
                                        ->label(__('franken-cms::messages.settings.general.form.title.label'))
                                        ->columnSpan(2),
                                    TextArea::make('tagline')
                                        ->inlineLabel()
                                        ->label(__('franken-cms::messages.settings.general.form.tagline.label'))
                                        ->columnSpan(2)
                                        ->helperText(__('franken-cms::messages.settings.general.form.tagline.helper')),

                                    FileUpload::make('icon')
                                        ->label(trans('franken-cms::messages.settings.general.form.icon.label'))
                                        ->inlineLabel()
                                        ->avatar()
                                        ->columnSpan(2)
                                        ->helperText(__('franken-cms::messages.settings.general.form.icon.helper')),

                                    Toggle::make('membership')
                                        ->inlineLabel()
                                        ->label(__('franken-cms::messages.settings.general.form.membership.label'))
                                        ->helperText(__('franken-cms::messages.settings.general.form.membership.helper'))
                                        ->required()
                                        ->columnSpan(2),

                                    Select::make('new_user_default_role')
                                        ->inlineLabel()
                                        ->required()
                                        ->label(__('franken-cms::messages.settings.general.form.default_user_role.label'))
                                        ->options(UserRole::class)
                                        ->selectablePlaceholder(false)
                                        ->default(UserRole::SUBSCRIBER->value)
                                        ->columnSpan(2),

                                    Select::make('timezone')
                                        ->inlineLabel()
                                        ->label(__('franken-cms::messages.settings.general.form.timezone.label'))
                                        ->helperText(__('franken-cms::messages.settings.general.form.timezone.helper'))
                                        ->options(TimezoneHelper::getGroupedTimezones())
                                        ->default('UTC+0')
                                        ->required()
                                        ->columnSpan(2),

                                    Radio::make('date_format')
                                        ->inlineLabel()
                                        ->live()
                                        ->label(__('franken-cms::messages.settings.general.form.date_format.label'))
                                        ->options(DateFormat::class)
                                        ->default(DateFormat::FULL_MONTH_DAY_YEAR->value)
                                        ->helperText(function (Get $get, $state) {
                                            $format = $state instanceof BackedEnum ? $state->value : $state;
                                            if ($format === DateFormat::CUSTOM->value) {
                                                $custom = $get('custom_date_format');
                                                if (is_string($custom) && $custom !== '') {
                                                    $format = $custom;
                                                }
                                            }
                                            return new HtmlString('<p><strong>Preview: </strong>' . now()->format((string) $format) . '</p>');
                                        })
                                        ->required()
                                        ->columnSpan(2),

                                    TextInput::make('custom_date_format')
                                        ->inlineLabel()
                                        ->label(__('franken-cms::messages.settings.general.form.custom_date_format.label'))
                                        ->columnSpan(2)
                                        ->visible(fn (Get $get) => $get('date_format') === DateFormat::CUSTOM->value),

                                    Radio::make('time_format')
                                        ->inlineLabel()
                                        ->live()
                                        ->label(__('franken-cms::messages.settings.general.form.time_format.label'))
                                        ->options(TimeFormat::class)
                                        ->default(TimeFormat::HOURS_12_MINUTES_LOWERCASE->value)
                                        ->helperText(function (Get $get, $state) {
                                            $format = $state instanceof BackedEnum ? $state->value : $state;
                                            if ($format === TimeFormat::CUSTOM->value) {
                                                $custom = $get('custom_time_format');
                                                if (is_string($custom) && $custom !== '') {
                                                    $format = $custom;
                                                }
                                            }
                                            return new HtmlString('<p><strong>Preview: </strong>' . now()->format((string) $format) . '</p>');
                                        })
                                        ->required()
                                        ->columnSpan(2),

                                    TextInput::make('custom_time_format')
                                        ->inlineLabel()
                                        ->label(__('franken-cms::messages.settings.general.form.custom_time_format.label'))
                                        ->columnSpan(2)
                                        ->visible(fn (Get $get) => $get('time_format') === TimeFormat::CUSTOM->value),

                                    Select::make('week_starts_on')
                                        ->inlineLabel()
                                        ->label(__('franken-cms::messages.settings.general.form.week_starts_on.label'))
                                        ->selectablePlaceholder(false)
                                        ->options(DayOfWeek::class)
                                        ->default(DayOfWeek::MONDAY->value)
                                        ->required()
                                        ->columnSpan(2),
                                ]),
                        ]),

                    Tab::make('Writing')
                        ->schema([
                            Section::make(__('franken-cms::messages.settings.writing.title'))
                                ->description(__('franken-cms::messages.settings.writing.description'))
                                ->columns(3)
                                ->columnSpanFull()
                                ->schema([]),
                        ]),

                    Tab::make('Reading')
                        ->columns(3)
                        ->schema([
                            Section::make(__('franken-cms::messages.settings.reading.title'))
                                ->description(__('franken-cms::messages.settings.reading.description'))
                                ->columns(3)
                                ->columnSpanFull()
                                ->schema([
                                    Radio::make('homepage_displays')
                                        ->live()
                                        ->inlineLabel()
                                        ->label('Your Homepage Displays')
                                        ->options([
                                            'latest_posts' => 'Your latest posts',
                                            'static_page'  => 'A static page (select below)',
                                        ])
                                        ->default('latest_posts')
                                        ->required()
                                        ->columnSpan(2),

                                    Select::make('home_page')
                                        ->label('Homepage')
                                        ->inlineLabel()
                                        ->required(fn (Get $get) => $get('homepage_displays') === 'static_page')
                                        ->visible(fn (Get $get) => $get('homepage_displays') === 'static_page')
                                        ->options(
                                            Page::query()->pluck('post_title', 'post_slug')
                                        )
                                        ->searchable()
                                        ->columnSpan(2),

                                    Select::make('post_page')
                                        ->label('Posts Page')
                                        ->inlineLabel()
                                        ->required(fn (Get $get) => $get('homepage_displays') === 'static_page')
                                        ->visible(fn (Get $get) => $get('homepage_displays') === 'static_page')
                                        ->options(
                                            Page::query()->pluck('post_title', 'post_slug')->toArray()
                                        )
                                        ->searchable()
                                        ->columnSpan(2),

                                    TextInput::make('posts_per_page')
                                        ->label('Blog Pages Show At Most')
                                        ->postfix('posts')
                                        ->inlineLabel()
                                        ->default(10)
                                        ->required()
                                        ->columnSpan(2),

                                    TextInput::make('syndicate_feeds')
                                        ->label('Syndicate Feeds Show The Most Recent')
                                        ->postfix('items')
                                        ->inlineLabel()
                                        ->default(10)
                                        ->required()
                                        ->columnSpan(2),

                                    Radio::make('include_in_feed')
                                        ->inlineLabel()
                                        ->label('For Each Article In A Feed, Include')
                                        ->helperText('Your theme determines how content is displayed in browsers.')
                                        ->options([
                                            'full_text' => 'Full Text',
                                            'summary'   => 'Summary',
                                        ])
                                        ->default('full_text')
                                        ->required()
                                        ->columnSpan(2),

                                    Checkbox::make('discourage_search_visibility')
                                        ->inlineLabel()
                                        ->label('Discourage search engines from indexing this site')
                                        ->helperText('It is up to search engines to honor this request.')
                                        ->columnSpan(2),
                                ]),
                        ]),

                    Tab::make('Discussion')
                        ->schema([
                            Section::make(__('franken-cms::messages.settings.discussion.title'))
                                ->description(__('franken-cms::messages.settings.discussion.description'))
                                ->columns(3)
                                ->columnSpanFull()
                                ->schema([]),
                        ]),

                    Tab::make('Media')
                        ->schema([
                            Section::make('Image Sizes')
                                ->description('The sizes listed below determine the maximum dimensions in pixels to use when adding an image to the Media Library.')
                                ->columnSpanFull()
                                ->schema([
                                    Fieldset::make('Thumbnail')
                                        ->columns(3)
                                        ->columnSpanFull()
                                        ->schema([
                                            TextInput::make('thumbnail_width')
                                                ->label('Width')
                                                ->inlineLabel()
                                                ->postfix('px')
                                                ->inlineLabel()
                                                ->default(150)
                                                ->required()
                                                ->columnSpan(2),
                                            TextInput::make('thumbnail_height')
                                                ->label('Height')
                                                ->inlineLabel()
                                                ->postfix('px')
                                                ->inlineLabel()
                                                ->default(150)
                                                ->required()
                                                ->columnSpan(2),
                                            Checkbox::make('thumbnail_crop')
                                                ->inlineLabel()
                                                ->label('Crop Thumbnail To Exact Dimensions')
                                                ->helperText('Normally thumbnails are proportional to the original image. Enable this to crop the thumbnail to exact dimensions.')
                                                ->columnSpan(2),
                                        ]),

                                    Fieldset::make('Medium Size')
                                        ->columns(3)
                                        ->columnSpanFull()
                                        ->schema([
                                            TextInput::make('medium_width')
                                                ->label('Width')
                                                ->inlineLabel()
                                                ->postfix('px')
                                                ->inlineLabel()
                                                ->default(300)
                                                ->required()
                                                ->columnSpan(2),
                                            TextInput::make('medium_height')
                                                ->label('Height')
                                                ->inlineLabel()
                                                ->postfix('px')
                                                ->inlineLabel()
                                                ->default(300)
                                                ->required()
                                                ->columnSpan(2),
                                        ]),

                                    Fieldset::make('Large Size')
                                        ->columns(3)
                                        ->columnSpanFull()
                                        ->schema([
                                            TextInput::make('large_width')
                                                ->label('Width')
                                                ->inlineLabel()
                                                ->postfix('px')
                                                ->inlineLabel()
                                                ->default(1024)
                                                ->required()
                                                ->columnSpan(2),
                                            TextInput::make('large_height')
                                                ->label('Height')
                                                ->inlineLabel()
                                                ->postfix('px')
                                                ->inlineLabel()
                                                ->default(1024)
                                                ->required()
                                                ->columnSpan(2),
                                        ]),
                                ]),
                        ]),

                    Tab::make('Permalinks')
                        ->schema([
                            Section::make(__('franken-cms::messages.settings.permalinks.title'))
                                ->description(__('franken-cms::messages.settings.permalinks.description'))
                                ->columns(3)
                                ->columnSpanFull()
                                ->schema([
                                    Fieldset::make('Common Settings')
                                        ->label('Common Settings')
                                        ->columns(3)
                                        ->columnSpanFull()
                                        ->schema([
                                            Html::make(new HtmlString(__('franken-cms::messages.settings.permalinks.form.placeholder.content')))
                                                ->key('common_settings_placeholder')
                                                ->columnSpanFull(),

                                            Radio::make('permalink_structure')
                                                ->live()
                                                ->inlineLabel()
                                                ->label('Permalink Structure')
                                                ->options(PermalinkStructure::class)
                                                ->default(PermalinkStructure::POST_NAME->value)
                                                ->required()
                                                ->columnSpan(2),

                                            Select::make('custom_permalink_structure')
                                                ->label('Custom Structure')
                                                ->visible(fn (Get $get) => $get('permalink_structure') === PermalinkStructure::CUSTOM->value)
                                                ->inlineLabel()
                                                ->rules(['required_if:permalink_structure,' . PermalinkStructure::CUSTOM->value, new PermalinkContainsPostPlaceholder])
                                                ->options(PermalinkTags::class)
                                                ->multiple()
                                                ->helperText(fn ($state) => implode('/', $state))
                                                ->columnSpan(2),

                                            Fieldset::make('Optional')
                                                ->columns(3)
                                                ->columnSpanFull()
                                                ->schema([
                                                    Html::make(new HtmlString(__('franken-cms::messages.settings.permalinks.form.optional_placeholder.content')))
                                                        ->key('optional_placeholder')
                                                        ->columnSpanFull(),

                                                    TextInput::make('category_base_url')
                                                        ->label('Category Base')
                                                        ->inlineLabel()
                                                        ->default('category')
                                                        ->required()
                                                        ->columnSpan(2),

                                                    TextInput::make('tag_base_url')
                                                        ->label('Tag Base')
                                                        ->inlineLabel()
                                                        ->default('tag')
                                                        ->required()
                                                        ->columnSpan(2),
                                                ]),
                                        ]),
                                ]),
                        ]),

                    Tab::make('Privacy')
                        ->schema([
                            Section::make(__('franken-cms::messages.settings.privacy.title'))
                                ->description(__('franken-cms::messages.settings.privacy.description'))
                                ->columns(3)
                                ->columnSpanFull()
                                ->schema([]),
                        ]),
                ]),
        ]);
    }
}
