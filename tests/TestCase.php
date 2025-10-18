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

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
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
