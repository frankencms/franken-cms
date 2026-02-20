<?php

namespace FrankenCms\Filament\Resources\UserBio\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use FrankenCms\Filament\Resources\UserBio\UserBioResource;

class EditUserBio extends EditRecord
{
    protected static string $resource = UserBioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->getRecord()->load('media');

        return $data;
    }
}
