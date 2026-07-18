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
use FrankenCms\Models\Post;
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
                // Process link_to value to get url and linkable fields
                $linkData = $this->processLinkTo($itemData['link_to'] ?? null, $itemData['url'] ?? null);

                $this->record->allMenuItems()->create([
                    'label'            => $itemData['label'],
                    'url'              => $linkData['url'],
                    'route_name'       => $itemData['route_name'] ?? null,
                    'route_parameters' => $itemData['route_parameters'] ?? [],
                    'target'           => $itemData['target'] ?? '_self',
                    'is_active'        => $itemData['is_active'] ?? true,
                    'linkable_type'    => $linkData['linkable_type'],
                    'linkable_id'      => $linkData['linkable_id'],
                    'additional_data'  => $itemData['additional_data'] ?? [],
                    'parent_id'        => $itemData['parent_id'] ?? null,
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

    /**
     * Process the link_to select value into url and linkable fields
     */
    protected function processLinkTo(?string $linkTo, ?string $customUrl): array
    {
        // Default values
        $result = [
            'url'           => null,
            'linkable_type' => null,
            'linkable_id'   => null,
        ];

        if (! $linkTo) {
            return $result;
        }

        // Custom URL - use the provided URL
        if ($linkTo === 'custom') {
            $result['url'] = $customUrl;

            return $result;
        }

        // Page or Post selection
        if (str_contains($linkTo, ':')) {
            [$type, $id] = explode(':', $linkTo);

            if ($type === 'page') {
                $page = \FrankenCms\Models\Page::find($id);
                if ($page) {
                    $result['url'] = $page->url;
                    $result['linkable_type'] = \FrankenCms\Models\Page::class;
                    $result['linkable_id'] = (int) $id;
                }
            } elseif ($type === 'post') {
                $post = Post::find($id);
                if ($post) {
                    $result['url'] = $post->url;
                    $result['linkable_type'] = Post::class;
                    $result['linkable_id'] = (int) $id;
                }
            }
        }

        return $result;
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
                        // Compute link_to value for the Select field
                        'link_to' => $this->computeLinkTo($item),
                    ];
                })
                ->toArray(),
        ];
    }

    /**
     * Compute the link_to select value from menu item data
     */
    protected function computeLinkTo($item): ?string
    {
        // Check if it's a page/post link via linkable relationship
        if ($item->linkable_type && $item->linkable_id) {
            if (str_contains($item->linkable_type, 'Page')) {
                return 'page:' . $item->linkable_id;
            } elseif (str_contains($item->linkable_type, 'Post')) {
                return 'post:' . $item->linkable_id;
            }
        }

        // If there's a URL but no linkable, it's a custom URL
        if ($item->url) {
            return 'custom';
        }

        return null;
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
