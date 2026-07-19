<?php

namespace FrankenCms\Tests;

use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use FrankenCms\FrankenCmsServiceProvider;
use FrankenCms\Tests\Support\TestPanelProvider;
use FrankenCms\Tests\Support\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
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
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            LaravelSettingsServiceProvider::class,
            SitemapServiceProvider::class,
            FrankenCmsServiceProvider::class,
            TestPanelProvider::class,
        ];
    }
}
