<?php

namespace FrankenCms\Filament\Resources\User\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use FrankenCms\Filament\Resources\User\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ensure the bio relationship is loaded with media
        $user = $this->getRecord();
        $user->load('bio.media');

        return $data;
    }
}
