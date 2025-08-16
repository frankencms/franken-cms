<?php

declare(strict_types=1);

namespace FrankenCms\Filament\Resources\User\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function make(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('email'),
            ]);
    }
}
