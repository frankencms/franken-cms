<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\Page\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use FrankenCms\Enums\PostType;
use FrankenCms\Factories\TemplateFieldFactory;
use FrankenCms\Filament\Forms\Components\TitleWithSlugInput;
use FrankenCms\Helpers\TemplateHelper;
use FrankenCms\Models\Page;

class PageForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Page Details')
                    ->columns(2)
                    ->columnSpanFull()
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
                            ->live()
                            ->options(fn () => self::getTemplates())
                            ->searchable()
                            ->disabled(fn ($livewire) => $livewire?->record !== null)
                            ->required()
                            ->placeholder('Select a template')
                            ->helperText('The template cannot be changed once the page is saved since each template has its own set of custom fields.'),
                    ]),

                Section::make('Custom Template Fields')
                    ->columnSpanFull()
                    ->schema(
                        function (Get $get) {
                            $templateName = $get('template');
                            return self::getTemplateFields($templateName);
                        }
                    ),
            ]);
    }

    private static function getTemplateFields(?string $templateName): array
    {
        return TemplateFieldFactory::createFromTemplate($templateName);
    }

    private static function getTemplates(): array
    {
        return TemplateHelper::getTemplates();
    }
}
