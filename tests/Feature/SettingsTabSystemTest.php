<?php

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;
use FrankenCms\Contracts\SettingsTabProviderInterface;
use FrankenCms\Services\SettingsTabService;
use FrankenCms\Settings\GeneralSettings;
use Spatie\LaravelSettings\Settings;


beforeEach(function () {
    // Configure Spatie Settings for testing
    config([
        'settings.default_repository' => 'database',
        'settings.repositories'       => [
            'database' => [
                'type'       => \Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository::class,
                'model'      => null,
                'table'      => 'settings',
                'connection' => null,
            ],
        ],
    ]);

    // Create settings table if it doesn't exist
    if (! \Schema::hasTable('settings')) {
        \Schema::create('settings', function ($table) {
            $table->id();
            $table->string('group');
            $table->string('name');
            $table->json('payload');
            $table->boolean('locked')->default(false);
            $table->timestamps();
            $table->unique(['group', 'name']);
        });
    }

    // Clear and initialize settings with default values
    \DB::table('settings')->truncate();
    \DB::table('settings')->insert([
        ['group' => 'cms_general', 'name' => 'title', 'payload' => json_encode('Default Title'), 'locked' => false],
        ['group' => 'cms_general', 'name' => 'tagline', 'payload' => json_encode(null), 'locked' => false],
        ['group' => 'cms_general', 'name' => 'icon', 'payload' => json_encode(null), 'locked' => false],
        ['group' => 'cms_general', 'name' => 'membership', 'payload' => json_encode(false), 'locked' => false],
        ['group' => 'cms_general', 'name' => 'new_user_default_role', 'payload' => json_encode('subscriber'), 'locked' => false],
        ['group' => 'cms_general', 'name' => 'language', 'payload' => json_encode(null), 'locked' => false],
        ['group' => 'cms_general', 'name' => 'timezone', 'payload' => json_encode('UTC+0'), 'locked' => false],
        ['group' => 'cms_general', 'name' => 'date_format', 'payload' => json_encode('F j, Y'), 'locked' => false],
        ['group' => 'cms_general', 'name' => 'custom_date_format', 'payload' => json_encode(null), 'locked' => false],
        ['group' => 'cms_general', 'name' => 'time_format', 'payload' => json_encode('g:i a'), 'locked' => false],
        ['group' => 'cms_general', 'name' => 'custom_time_format', 'payload' => json_encode(null), 'locked' => false],
    ]);
});

it('can load and save settings through the tab system', function () {
    // Register default tabs
    $settingsTabService = app(SettingsTabService::class);
    $settingsTabService->registerDefaultTabs();

    // Verify tabs are registered
    $registry = $settingsTabService->getRegistry();
    expect($registry->hasProvider('general'))->toBeTrue();

    // Test settings can be loaded
    $generalSettings = app(GeneralSettings::class);
    expect($generalSettings->title)->toBe('Default Title');
    expect($generalSettings->tagline)->toBeNull();
    expect($generalSettings->membership)->toBe(false);

    // Test settings can be updated
    $generalSettings->title = 'Updated Title';
    $generalSettings->tagline = 'Updated Tagline';
    $generalSettings->membership = true;
    $generalSettings->save();

    // Verify changes persisted
    $freshSettings = app(GeneralSettings::class);
    expect($freshSettings->title)->toBe('Updated Title');
    expect($freshSettings->tagline)->toBe('Updated Tagline');
    expect($freshSettings->membership)->toBe(true);
});

it('can register and use custom tabs', function () {
    // Create a test settings class
    $customSettings = new class extends Settings
    {
        public string $custom_field = 'default';
        public bool $custom_enabled = false;

        public static function group(): string
        {
            return 'test_custom';
        }
    };

    // Add database entries for custom settings
    \DB::table('settings')->insert([
        ['group' => 'test_custom', 'name' => 'custom_field', 'payload' => json_encode('default'), 'locked' => false],
        ['group' => 'test_custom', 'name' => 'custom_enabled', 'payload' => json_encode(false), 'locked' => false],
    ]);

    // Create a test tab provider
    $customProvider = new class($customSettings) implements SettingsTabProviderInterface
    {
        public function __construct(private $settingsClass) {}

        public function getTab(): Tab
        {
            return Tab::make('Custom Tab')
                ->schema([
                    TextInput::make('custom_field')->label('Custom Field'),
                ]);
        }

        public function getSettingsClass(): string
        {
            return get_class($this->settingsClass);
        }

        public function getOrder(): int
        {
            return 100;
        }

        public function getTabKey(): string
        {
            return 'custom';
        }
    };

    // Register the custom tab
    $settingsTabService = app(SettingsTabService::class);
    $settingsTabService->registerDefaultTabs();
    $settingsTabService->registerTab($customProvider);

    // Verify custom tab is registered
    $registry = $settingsTabService->getRegistry();
    expect($registry->hasProvider('custom'))->toBeTrue();

    // Verify tabs are ordered correctly
    $providers = $registry->getProviders();
    $keys = $providers->keys()->toArray();
    expect($keys)->toContain('general');
    expect($keys)->toContain('custom');

    // Custom tab should appear after general (order 100 > 10)
    $generalIndex = array_search('general', $keys);
    $customIndex = array_search('custom', $keys);
    expect($customIndex)->toBeGreaterThan($generalIndex);
});

it('handles multiple settings classes correctly', function () {
    // Set up multiple settings - ReadingSettings
    \DB::table('settings')->insert([
        ['group' => 'cms_reading', 'name' => 'posts_per_page', 'payload' => json_encode(10), 'locked' => false],
        ['group' => 'cms_reading', 'name' => 'for_each_post_in_a_feed_include', 'payload' => json_encode('excerpt'), 'locked' => false],
        ['group' => 'cms_reading', 'name' => 'for_each_post_in_a_feed_show_the_most_recent', 'payload' => json_encode(10), 'locked' => false],
        ['group' => 'cms_reading', 'name' => 'search_engine_visibility', 'payload' => json_encode(false), 'locked' => false],
        // MediaSettings
        ['group' => 'cms_media', 'name' => 'thumbnail_size_w', 'payload' => json_encode(150), 'locked' => false],
        ['group' => 'cms_media', 'name' => 'thumbnail_size_h', 'payload' => json_encode(150), 'locked' => false],
        ['group' => 'cms_media', 'name' => 'thumbnail_crop', 'payload' => json_encode(false), 'locked' => false],
        ['group' => 'cms_media', 'name' => 'medium_size_w', 'payload' => json_encode(300), 'locked' => false],
        ['group' => 'cms_media', 'name' => 'medium_size_h', 'payload' => json_encode(300), 'locked' => false],
        ['group' => 'cms_media', 'name' => 'medium_crop', 'payload' => json_encode(false), 'locked' => false],
        ['group' => 'cms_media', 'name' => 'large_size_w', 'payload' => json_encode(1024), 'locked' => false],
        ['group' => 'cms_media', 'name' => 'large_size_h', 'payload' => json_encode(1024), 'locked' => false],
        ['group' => 'cms_media', 'name' => 'large_crop', 'payload' => json_encode(false), 'locked' => false],
        ['group' => 'cms_media', 'name' => 'uploads_use_yearmonth_folders', 'payload' => json_encode(false), 'locked' => false],
    ]);

    $settingsTabService = app(SettingsTabService::class);
    $settingsTabService->registerDefaultTabs();

    // Verify multiple settings classes are registered
    $registry = $settingsTabService->getRegistry();
    $settingsClasses = $registry->getSettingsClasses();

    expect($settingsClasses)->toContain(GeneralSettings::class);
    expect($settingsClasses)->toContain(\FrankenCms\Settings\ReadingSettings::class);
    expect($settingsClasses)->toContain(\FrankenCms\Settings\MediaSettings::class);

    // Test that each settings class works independently
    $generalSettings = app(GeneralSettings::class);
    $readingSettings = app(\FrankenCms\Settings\ReadingSettings::class);

    expect($generalSettings->title)->toBe('Default Title');
    expect($readingSettings->posts_per_page)->toBe(10);

    // Update one settings class
    $generalSettings->title = 'New Title';
    $generalSettings->save();

    // Verify other settings are unchanged
    $freshReadingSettings = app(\FrankenCms\Settings\ReadingSettings::class);
    expect($freshReadingSettings->posts_per_page)->toBe(10);
});

it('maintains tab ordering across multiple registrations', function () {
    $settingsTabService = app(SettingsTabService::class);

    // Register tabs in mixed order
    $settingsTabService->registerDefaultTabs();

    // Add a custom tab with middle order
    $middleProvider = new class implements SettingsTabProviderInterface
    {
        public function getTab(): Tab
        {
            return Tab::make('Middle Tab');
        }

        public function getSettingsClass(): string
        {
            return 'MiddleSettings';
        }

        public function getOrder(): int
        {
            return 15;
        } // Between general (10) and reading (20)

        public function getTabKey(): string
        {
            return 'middle';
        }
    };

    $settingsTabService->registerTab($middleProvider);

    // Verify ordering
    $registry = $settingsTabService->getRegistry();
    $providers = $registry->getProviders();
    $keys = $providers->keys()->toArray();

    $generalIndex = array_search('general', $keys);
    $middleIndex = array_search('middle', $keys);
    $readingIndex = array_search('reading', $keys);

    expect($generalIndex)->toBeLessThan($middleIndex);
    expect($middleIndex)->toBeLessThan($readingIndex);
});

it('handles edge cases gracefully', function () {
    $settingsTabService = app(SettingsTabService::class);

    // Test duplicate key handling
    $provider1 = new class implements SettingsTabProviderInterface
    {
        public function getTab(): Tab
        {
            return Tab::make('First');
        }

        public function getSettingsClass(): string
        {
            return 'FirstSettings';
        }

        public function getOrder(): int
        {
            return 10;
        }

        public function getTabKey(): string
        {
            return 'duplicate';
        }
    };

    $provider2 = new class implements SettingsTabProviderInterface
    {
        public function getTab(): Tab
        {
            return Tab::make('Second');
        }

        public function getSettingsClass(): string
        {
            return 'SecondSettings';
        }

        public function getOrder(): int
        {
            return 20;
        }

        public function getTabKey(): string
        {
            return 'duplicate';
        }
    };

    $settingsTabService->registerTab($provider1);
    $settingsTabService->registerTab($provider2);

    // Get registry after registration
    $registry = $settingsTabService->getRegistry();

    // Should have the second provider (overriding the first)
    expect($registry->hasProvider('duplicate'))->toBeTrue();
    expect($registry->getProvider('duplicate'))->toBe($provider2);
});
