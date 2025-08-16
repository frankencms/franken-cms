<?php

namespace FrankenCms\Filament\Resources\User\Pages;

use Filament\Resources\Pages\CreateRecord;
use FrankenCms\Filament\Resources\User\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
