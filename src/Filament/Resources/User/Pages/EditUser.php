<?php

namespace FrankenCms\Filament\Resources\User\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use FrankenCms\Filament\Resources\User\UserResource;
use Illuminate\Database\Eloquent\Model;

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
        // Load the bio relationship if it exists
        $user = $this->getRecord();
        if ($user->bio) {
            $data['bio'] = $user->bio->toArray();
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Extract bio data
        $bioData = $data['bio'] ?? [];
        unset($data['bio']);

        // Update the user
        $record->update($data);

        // Update or create the bio
        if (! empty(array_filter($bioData))) {
            $record->bio()->updateOrCreate(
                ['user_id' => $record->id],
                $bioData
            );
        }

        return $record;
    }
}
