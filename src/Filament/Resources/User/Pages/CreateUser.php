<?php

namespace FrankenCms\Filament\Resources\User\Pages;

use Filament\Resources\Pages\CreateRecord;
use FrankenCms\Filament\Resources\User\UserResource;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Extract bio data
        $bioData = $data['bio'] ?? [];
        unset($data['bio']);

        // Create the user
        $record = static::getModel()::create($data);

        // Create the bio if any data was provided
        if (! empty(array_filter($bioData))) {
            $record->bio()->create($bioData);
        }

        return $record;
    }
}
