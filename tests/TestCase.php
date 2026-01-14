<?php

namespace FrankenCms\Tests;

use FrankenCms\FrankenCmsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

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
        config()->set('franken-cms.models.user', \FrankenCms\Tests\Support\User::class);

        // Configure Spatie Media Library for tests
        config()->set('media-library.media_model', \Spatie\MediaLibrary\MediaCollections\Models\Media::class);
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
            \Livewire\LivewireServiceProvider::class,
            \Filament\FilamentServiceProvider::class,
            \Filament\Actions\ActionsServiceProvider::class,
            \Filament\Forms\FormsServiceProvider::class,
            \Filament\Infolists\InfolistsServiceProvider::class,
            \Filament\Notifications\NotificationsServiceProvider::class,
            \Filament\Support\SupportServiceProvider::class,
            \Filament\Tables\TablesServiceProvider::class,
            \Filament\Widgets\WidgetsServiceProvider::class,
            \Spatie\LaravelSettings\LaravelSettingsServiceProvider::class,
            \Spatie\Sitemap\SitemapServiceProvider::class,
            FrankenCmsServiceProvider::class,
            \FrankenCms\Tests\Support\TestPanelProvider::class,
        ];
    }
}
