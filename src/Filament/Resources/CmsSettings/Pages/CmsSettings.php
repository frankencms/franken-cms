<?php

namespace FrankenCms\Filament\Resources\CmsSettings\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use FrankenCms\Services\SettingsTabService;
use Illuminate\Contracts\Support\Htmlable;

class CmsSettings extends Page implements HasForms
{
    use InteractsWithForms;

    public array $data = [];

    public function getView(): string
    {
        return 'franken-cms::filament.pages.cms-settings';
    }

    protected static string | BackedEnum | null $navigationIcon = Heroicon::AdjustmentsVertical;

    protected static ?string $description = 'Configure your site settings.';
    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): string
    {
        return config('franken-cms.navigation_group_name');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('franken-cms::messages.settings.label');
    }

    public function getTitle(): string
    {
        return __('franken-cms::messages.settings.general.title');
    }

    public function getSubheading(): string | Htmlable | null
    {
        return __('franken-cms::messages.settings.general.description');
    }

    public function mount(): void
    {
        $this->loadAllSettingsData();
    }

    protected function loadAllSettingsData(): void
    {
        $settingsTabService = app(SettingsTabService::class);
        $providers = $settingsTabService->getRegistry()->getProviders();

        $this->data = [];

        foreach ($providers as $provider) {
            $settingsClass = $provider->getSettingsClass();
            $settings = app($settingsClass);

            // Get all properties defined in the settings class using reflection
            $reflection = new \ReflectionClass($settingsClass);
            $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                $propertyName = $property->getName();
                // Use the settings getter method to get the actual persisted value
                $this->data[$propertyName] = $settings->{$propertyName};
            }
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        $settingsTabService = app(SettingsTabService::class);

        return $schema
            ->components([
                \Filament\Schemas\Components\Form::make([
                    Tabs::make('Tabs')
                        ->persistTabInQueryString('settings-tab')
                        ->columnSpanFull()
                        ->tabs($settingsTabService->getRegistry()->getTabs()),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        \Filament\Schemas\Components\Actions::make([
                            \Filament\Actions\Action::make('save')
                                ->label(__('filament-spatie-laravel-settings-plugin::pages/settings-page.form.actions.save.label'))
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            $settingsTabService = app(SettingsTabService::class);
            $providers = $settingsTabService->getRegistry()->getProviders();

            foreach ($providers as $provider) {
                $settingsClass = $provider->getSettingsClass();
                $settings = app($settingsClass);

                // Get all properties defined in the settings class using reflection
                $reflection = new \ReflectionClass($settingsClass);
                $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

                // Update only the properties that belong to this settings class
                $hasChanges = false;
                $settingsData = [];

                foreach ($properties as $property) {
                    $propertyName = $property->getName();
                    if (array_key_exists($propertyName, $data)) {
                        $settingsData[$propertyName] = $data[$propertyName];
                        $hasChanges = true;
                    }
                }

                // Only save if there were changes for this settings class
                if ($hasChanges) {
                    foreach ($settingsData as $property => $value) {
                        $settings->{$property} = $value;
                    }
                    $settings->save();
                }
            }

        } catch (Halt $exception) {
            return;
        }

        Notification::make()
            ->title(__('filament-spatie-laravel-settings-plugin::pages/settings-page.notifications.saved.title'))
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
