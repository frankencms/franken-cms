<?php

namespace FrankenCms\Filament\Resources\CmsSettings\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use FrankenCms\Services\SettingsTabService;
use Illuminate\Contracts\Support\Htmlable;
use ReflectionClass;
use ReflectionProperty;

/**
 * @property-read Schema $form
 */
class CmsSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::AdjustmentsVertical;

    protected static ?string $description = 'Configure your site settings.';
    protected static ?int $navigationSort = 6;

    public ?array $data = [];

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

    public function getView(): string
    {
        return 'franken-cms::filament.pages.cms-settings';
    }

    public function getTitle(): string
    {
        return __('franken-cms::messages.settings.general.title');
    }

    public function getSubheading(): string | Htmlable | null
    {
        return __('franken-cms::messages.settings.general.description');
    }

    public function boot(): void
    {
        // Initialize data as empty array to prevent null issues
        if (! is_array($this->data)) {
            $this->data = [];
        }
    }

    public function mount(): void
    {
        $this->loadAllSettingsData();

        $this->form->fill($this->data ?? []);
    }

    public function form(Schema $schema): Schema
    {
        $settingsTabService = app(SettingsTabService::class);

        return $schema
            ->components([
                Form::make([
                    Tabs::make('Tabs')
                        ->persistTabInQueryString('settings-tab')
                        ->columnSpanFull()
                        ->tabs($settingsTabService->getRegistry()->getTabs()),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
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
                $group = $settings::group();

                // Skip if no data for this group
                if (! isset($data[$group]) || ! is_array($data[$group])) {
                    continue;
                }

                // Get all properties defined in the settings class using reflection
                $reflection = new ReflectionClass($settingsClass);
                $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

                // Update only the properties that belong to this settings class
                $hasChanges = false;
                $settingsData = [];

                foreach ($properties as $property) {
                    $propertyName = $property->getName();
                    if (array_key_exists($propertyName, $data[$group])) {
                        $settingsData[$propertyName] = $data[$group][$propertyName];
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

    protected function getValidationAttributes(): array
    {
        return [];
    }

    protected function getValidationMessages(): array
    {
        return [];
    }

    protected function loadAllSettingsData(): void
    {
        $settingsTabService = app(SettingsTabService::class);
        $providers = $settingsTabService->getRegistry()->getProviders();

        $this->data = [];

        foreach ($providers as $provider) {
            $settingsClass = $provider->getSettingsClass();
            $settings = app($settingsClass);
            $group = $settings::group();

            // Get all properties defined in the settings class using reflection
            $reflection = new ReflectionClass($settingsClass);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

            // Initialize the group array if it doesn't exist
            if (! isset($this->data[$group])) {
                $this->data[$group] = [];
            }

            foreach ($properties as $property) {
                $propertyName = $property->getName();
                // Use the settings getter method to get the actual persisted value
                // Store under the settings group to avoid conflicts
                $this->data[$group][$propertyName] = $settings->{$propertyName};
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
