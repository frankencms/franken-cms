<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\User\Schemas;

use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use FrankenCms\Services\SocialLinksService;

class UserForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->inlineLabel()
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ]),

                Section::make('Author Biography')
                    ->description('This information will be displayed on blog posts authored by this user.')
                    ->relationship('bio')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('bio_image')
                            ->label('Profile Image')
                            ->inlineLabel()
                            ->collection('bio-image')
                            ->disk(config('franken-cms.media_disk_name', 'public'))
                            ->image()
                            ->imageEditor()
                            ->avatar(config('franken-cms.user_bio.image_shape', 'circle') === 'circle')
                            ->circleCropper(config('franken-cms.user_bio.image_shape', 'circle') === 'circle')
                            ->imageAspectRatio('1:1')
                            ->automaticallyOpenImageEditorForAspectRatio()
                            ->previewable()
                            ->maxSize(5120) // 5MB
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->visibility('public')
                            ->multiple(false)
                            ->helperText('Square images work best. Recommended size: 400x400px'),

                        TextInput::make('title')
                            ->label('Job Title')
                            ->inlineLabel()
                            ->placeholder('e.g., Senior Developer, Content Writer')
                            ->maxLength(255),
                        RichEditor::make('bio')
                            ->label('Biography')
                            ->inlineLabel()
                            ->placeholder('Tell us about yourself...')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'link',
                                'bulletList',
                                'orderedList',
                                'undo',
                                'redo',
                            ])
                            ->maxLength(65535),
                        TextInput::make('website')
                            ->label('Website')
                            ->inlineLabel()
                            ->url()
                            ->placeholder('https://example.com')
                            ->maxLength(255),
                        Repeater::make('social_links')
                            ->label('Social Media Links')
                            ->inlineLabel()
                            ->addActionLabel('Add social link')
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => self::getSocialLinkItemLabel($state))
                            ->schema([
                                Select::make('platform')
                                    ->label('Platform')
                                    ->options(fn () => app(SocialLinksService::class)->getPlatformOptions())
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->columnSpan(1),
                                TextInput::make('value')
                                    ->label('Username or URL')
                                    ->placeholder(fn ($get) => self::getSocialLinkPlaceholder($get('platform')))
                                    ->helperText('Enter a username (e.g., @johndoe) or full URL')
                                    ->required()
                                    ->rules([
                                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                            if (empty($value)) {
                                                return;
                                            }
                                            $service = app(SocialLinksService::class);
                                            $result = $service->validateValue('', (string) $value);
                                            if ($result !== true) {
                                                $fail($result);
                                            }
                                        },
                                    ])
                                    ->columnSpan(2),
                            ])
                            ->columns(3)
                            ->defaultItems(0),
                    ])
                    ->collapsible(),
            ]);
    }

    /**
     * Get the item label for a social link repeater item
     */
    protected static function getSocialLinkItemLabel(array $state): ?string
    {
        $platform = $state['platform'] ?? null;
        $value = $state['value'] ?? null;

        if (! $platform) {
            return null;
        }

        $service = app(SocialLinksService::class);
        $config = $service->getPlatform($platform);
        $label = $config['label'] ?? ucfirst($platform);

        if ($value) {
            // Show shortened value (username or truncated URL)
            $displayValue = $service->isUrl($value)
                ? $service->extractUsername($platform, $value)
                : $value;

            return "{$label}: {$displayValue}";
        }

        return $label;
    }

    /**
     * Get the placeholder text for a social link value input
     */
    protected static function getSocialLinkPlaceholder(?string $platform): string
    {
        if (! $platform) {
            return 'Select a platform first';
        }

        return app(SocialLinksService::class)->getPlaceholder($platform);
    }
}
