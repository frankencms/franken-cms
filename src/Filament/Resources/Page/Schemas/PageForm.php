<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Page\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use FrankenCms\Enums\PostType;
use FrankenCms\Factories\TemplateFieldFactory;
use FrankenCms\Filament\Forms\Components\TitleWithSlugInput;
use FrankenCms\Filament\Resources\Concerns\HasSeoFields;
use FrankenCms\Helpers\TemplateHelper;
use FrankenCms\Models\Page;
use FrankenCms\Models\Post;
use Illuminate\Support\HtmlString;

class PageForm
{
    use HasSeoFields;

    public static function make(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Page Editor')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        // Settings Tab
                        Tab::make('Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Page Details')
                                    ->columns(1)
                                    ->schema([
                                        Hidden::make('post_type')
                                            ->default(PostType::PAGE->value),

                                        TitleWithSlugInput::make(
                                            fieldTitle: 'post_title',
                                            fieldSlug: 'post_slug',
                                            titleLabel: 'Page Name',
                                            slugLabel: 'Permalink',
                                            slugRules: [
                                                'required',
                                                fn (?Page $record) => 'unique:posts,post_slug,' . ($record?->id ?? 'NULL') . ',id',
                                            ],
                                        ),

                                        Select::make('template')
                                            ->label('Page Template')
                                            ->inlineLabel()
                                            ->live()
                                            ->options(fn () => self::getTemplates())
                                            ->searchable()
                                            ->disabled(fn ($livewire) => $livewire?->record !== null)
                                            ->required()
                                            ->placeholder('Select a template')
                                            ->helperText('The template cannot be changed once the page is saved since each template has its own set of custom fields.'),

                                        Select::make('parent_id')
                                            ->label('Parent Page')
                                            ->inlineLabel()
                                            ->live()
                                            ->options(function ($livewire) {
                                                $query = Post::withoutGlobalScopes()
                                                    ->where('post_type', 'page')
                                                    ->where('post_status', 'published');

                                                if ($livewire->record) {
                                                    $query->where('id', '!=', $livewire->record->id);
                                                }

                                                return $query->pluck('post_title', 'id');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->helperText('Select a parent page to create a hierarchical structure (e.g., /about/team).'),

                                        TextEntry::make('page_url_preview')
                                            ->label('Page URL')
                                            ->inlineLabel()
                                            ->state(function (Get $get, $livewire) {
                                                $slug = $get('post_slug');
                                                $parentId = $get('parent_id');

                                                if (! $slug) {
                                                    return 'Enter a permalink to see the URL';
                                                }

                                                // Build hierarchical path
                                                $segments = [];
                                                if ($parentId) {
                                                    $parent = Page::withoutGlobalScopes()->find($parentId);
                                                    if ($parent) {
                                                        // Get all parent segments
                                                        $ancestors = $parent->ancestors();
                                                        foreach ($ancestors as $ancestor) {
                                                            $segments[] = $ancestor->post_slug;
                                                        }
                                                        $segments[] = $parent->post_slug;
                                                    }
                                                }
                                                $segments[] = $slug;

                                                $path = '/' . implode('/', $segments);
                                                $fullUrl = url($path);

                                                return new HtmlString(
                                                    '<a href="' . $fullUrl . '" target="_blank" class="text-primary-600 hover:underline">' .
                                                    $fullUrl .
                                                    '</a>'
                                                );
                                            })
                                            ->columnSpanFull(),

                                        TextInput::make('route_name')
                                            ->label('Route Name')
                                            ->inlineLabel()
                                            ->unique('posts', 'route_name', ignoreRecord: true)
                                            ->nullable()
                                            ->regex('/^[a-zA-Z0-9._-]+$/')
                                            ->validationMessages([
                                                'regex' => 'The route name may only contain letters, numbers, dots, underscores, and hyphens.',
                                            ])
                                            ->helperText('Named route for this page. If left empty, will automatically use the page slug. Use route("route.name") in your templates.')
                                            ->placeholder('Leave empty to use slug'),
                                    ]),
                            ]),

                        // Content Tab (Template Fields)
                        Tab::make('Content')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Custom Template Fields')
                                    ->schema(
                                        function (Get $get) {
                                            $templateName = $get('template');
                                            return self::getTemplateFields($templateName);
                                        }
                                    ),
                            ]),

                        // SEO Tab
                        self::getSeoTab(),
                    ]),
            ]);
    }

    private static function getTemplateFields(?string $templateName): array
    {
        return TemplateFieldFactory::createFromTemplate($templateName);
    }

    private static function getTemplates(): array
    {
        return TemplateHelper::getPageTemplates();
    }
}
