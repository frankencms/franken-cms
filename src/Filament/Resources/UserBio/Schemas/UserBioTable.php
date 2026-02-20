<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\UserBio\Schemas;

use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserBioTable
{
    public static function make(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('bio_image')
                    ->label('')
                    ->collection('bio-image')
                    ->conversion('bio-thumb')
                    ->circular(config('franken-cms.user_bio.image_shape', 'circle') === 'circle')
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->user->name ?? '?') . '&color=7F9CF5&background=EBF4FF')
                    ->imageSize(40),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Job Title')
                    ->searchable()
                    ->placeholder('No title'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->defaultSort('created_at', 'desc');
    }
}
