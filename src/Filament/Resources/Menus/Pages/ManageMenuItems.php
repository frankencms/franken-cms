<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Menus\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use FrankenCms\Filament\Resources\Menus\MenuResource;
use FrankenCms\Filament\Resources\Menus\Schemas\MenuItems;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @property-read Schema $form
 */
class ManageMenuItems extends Page implements HasSchemas
{
    use InteractsWithRecord;
    use InteractsWithSchemas;

    protected static string $resource = MenuResource::class;

    public ?array $data = [];

    public function mount(int | string $record): void
    {
        $this->record = static::getResource()::resolveRecordRouteBinding($record);
        $this->loadMenuItemsData();
        $this->form->fill($this->data);
    }

    public function getTitle(): string | Htmlable
    {
        return "Manage Menu Items - {$this->record->name}";
    }

    public function getView(): string
    {
        return 'franken-cms::filament.pages.manage-menu-items';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url()->route('filament.admin.resources.menus.index') => 'Menus',
            '#'                                                  => $this->getTitle(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return MenuItems::make($schema)
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            // Delete existing menu items
            $this->record->allMenuItems()->delete();

            // Create new menu items
            foreach ($data['menu_items'] as $index => $itemData) {
                $this->record->allMenuItems()->create([
                    'label'            => $itemData['label'],
                    'url'              => $itemData['url'],
                    'route_name'       => $itemData['route_name'],
                    'route_parameters' => $itemData['route_parameters'] ?? [],
                    'target'           => $itemData['target'] ?? '_self',
                    'is_active'        => $itemData['is_active'] ?? true,
                    'linkable_type'    => $itemData['linkable_type'],
                    'linkable_id'      => $itemData['linkable_id'],
                    'additional_data'  => $itemData['additional_data'] ?? [],
                    'parent_id'        => $itemData['parent_id'],
                    'sort_order'       => $index,
                ]);
            }

            // Clear menu cache
            $this->record->clearCache();

        } catch (Halt $exception) {
            return;
        }

        Notification::make()
            ->title('Menu items saved successfully')
            ->success()
            ->send();
    }

    protected function loadMenuItemsData(): void
    {
        $this->data = [
            'menu_items' => $this->record->allMenuItems()
                ->with('linkable')
                ->orderBy('sort_order')
                ->get()
                ->map(function ($item) {
                    return [
                        'id'               => $item->id,
                        'label'            => $item->label,
                        'url'              => $item->url,
                        'route_name'       => $item->route_name,
                        'route_parameters' => $item->route_parameters,
                        'target'           => $item->target,
                        'is_active'        => $item->is_active,
                        'linkable_type'    => $item->linkable_type,
                        'linkable_id'      => $item->linkable_id,
                        'additional_data'  => $item->additional_data,
                        'parent_id'        => $item->parent_id,
                        'sort_order'       => $item->sort_order,
                    ];
                })
                ->toArray(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('Back to Menus'))
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),

            Action::make('save')
                ->label(__('Save Menu Items'))
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }
}
