<?php

namespace FrankenCms\Filament\Resources\Page;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use FrankenCms\Enums\PostType;
use FrankenCms\Factories\TemplateFieldFactory;
use FrankenCms\Forms\Components\TitleWithSlugInput;
use FrankenCms\Helpers\TemplateHelper;
use FrankenCms\Models\Page;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return config('franken-cms.navigation_group_name');
    }

    public static function form(Form $form): Form
    {

        return $form
            ->schema([

                Section::make('Page Details')
                    ->columns(2)
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
                            ->helperText('The template cannot be changed once the page is  saved since each template has its own set of custom fields.'),

                    ]),

                Section::make('Custom Template Fields')
                    ->schema(
                        function (Get $get) {
                            $templateName = $get('template');
                            return self::getTemplateFields($templateName);
                        }
                    ),

            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('post_title')->sortable()->searchable(),
                TextColumn::make('post_slug')->sortable()->searchable(),
                TextColumn::make('terms.name')->label('Terms')->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit'   => Pages\EditPage::route('/{record}/edit'),
        ];
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
