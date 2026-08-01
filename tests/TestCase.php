<?php

namespace FrankenCms\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use FrankenCms\FrankenCmsServiceProvider;
use FrankenCms\Tests\Support\TestPanelProvider;
use FrankenCms\Tests\Support\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Ai\AiServiceProvider;
use Laravel\Head\HeadServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Spatie\OgImage\OgImageServiceProvider;
use Spatie\Sitemap\SitemapServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'FrankenCms\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        config()->set('app.timezone', 'UTC');
        config()->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        // Configure FrankenCMS user model for tests
        config()->set('franken-cms.models.user', User::class);

        // Configure Spatie Media Library for tests
        config()->set('media-library.media_model', Media::class);
        config()->set('media-library.disk_name', 'public');
        config()->set('media-library.max_file_size', 1024 * 1024 * 10); // 10MB

        // Fixture views used by feature tests (e.g. OG image template mapping)
        $app['view']->addNamespace('test-fixtures', __DIR__ . '/fixtures/views');
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        // Load test-specific migrations first (users table, etc.)
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            // Filament's SupportServiceProvider rebinds Livewire\Mechanisms\DataStore
            // via a non-shared `bind()` (Filament\Support\Livewire\Partials\DataStoreOverride),
            // which overrides the shared `instance()` binding Livewire's own service
            // provider sets up. Provider `register()` methods run in list order, so
            // LivewireServiceProvider must be registered LAST to have its singleton
            // binding win — otherwise DataStore is re-resolved fresh on every call
            // (empty WeakMap each time), breaking component state such as validation
            // error bags for any Livewire component rendered in tests.
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            FilamentServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            LaravelSettingsServiceProvider::class,
            AiServiceProvider::class,
            SitemapServiceProvider::class,
            MediaLibraryServiceProvider::class,
            OgImageServiceProvider::class,
            HeadServiceProvider::class,
            FrankenCmsServiceProvider::class,
            TestPanelProvider::class,
            LivewireServiceProvider::class,
        ];
    }
}
