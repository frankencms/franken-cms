<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Menus\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use FrankenCms\Filament\Resources\Menus\MenuResource;
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
        return $schema
            ->components([
                Form::make([
                    Section::make('Menu Items')
                        ->description('Drag and drop to reorder menu items. Nested items will appear as submenus.')
                        ->schema([
                            Repeater::make('menu_items')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('label')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpan(1),

                                            Select::make('target')
                                                ->options([
                                                    '_self'   => 'Same Window',
                                                    '_blank'  => 'New Window',
                                                    '_parent' => 'Parent Frame',
                                                    '_top'    => 'Top Frame',
                                                ])
                                                ->default('_self')
                                                ->columnSpan(1),

                                            Toggle::make('is_active')
                                                ->label('Active')
                                                ->default(true)
                                                ->columnSpan(1),
                                        ]),

                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('url')
                                                ->label('Custom URL')
                                                ->url()
                                                ->placeholder('https://example.com')
                                                ->helperText('Leave empty to use route or linkable model')
                                                ->columnSpan(1),

                                            TextInput::make('route_name')
                                                ->label('Route Name')
                                                ->placeholder('post.show')
                                                ->helperText('Laravel route name')
                                                ->columnSpan(1),
                                        ]),

                                    Select::make('linkable_type')
                                        ->label('Link Type')
                                        ->options([
                                            ''          => 'None',
                                            Post::class => 'Post',
                                        ])
                                        ->live()
                                        ->columnSpan(1),

                                    Select::make('linkable_id')
                                        ->label('Select Content')
                                        ->options(function (callable $get) {
                                            $type = $get('linkable_type');
                                            if ($type === Post::class) {
                                                return Post::pluck('post_title', 'id')->toArray();
                                            }
                                            return [];
                                        })
                                        ->searchable()
                                        ->visible(fn (callable $get) => ! empty($get('linkable_type')))
                                        ->columnSpan(1),

                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('css_class')
                                                ->label('CSS Class')
                                                ->placeholder('nav-link active')
                                                ->columnSpan(1),

                                            TextInput::make('icon')
                                                ->label('Icon')
                                                ->placeholder('heroicon-o-home')
                                                ->columnSpan(1),

                                            TextInput::make('parent_id')
                                                ->label('Parent Item ID')
                                                ->numeric()
                                                ->helperText('Leave empty for top-level items')
                                                ->columnSpan(1),
                                        ]),

                                    KeyValue::make('additional_data')
                                        ->label('Additional Data')
                                        ->keyLabel('Key')
                                        ->valueLabel('Value')
                                        ->columnSpanFull(),
                                ])
                                ->defaultItems(0)
                                ->reorderable()
                                ->collapsible()
                                ->cloneable()
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                ->columnSpanFull(),
                        ]),
                ])
                    ->livewireSubmitHandler('save'),
            ])
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
                    'css_class'        => $itemData['css_class'],
                    'icon'             => $itemData['icon'],
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
                        'css_class'        => $item->css_class,
                        'icon'             => $item->icon,
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
                ->label('Back to Menus')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),

            Action::make('save')
                ->label('Save Menu Items')
                ->action('save')
                ->keyBindings(['mod+s']),
        ];
    }
}
