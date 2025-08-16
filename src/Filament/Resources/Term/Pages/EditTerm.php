<?php

namespace FrankenCms\Filament\Resources\Term\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use FrankenCms\Filament\Resources\Term\TermResource;

class EditTerm extends EditRecord
{
    protected static string $resource = TermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
