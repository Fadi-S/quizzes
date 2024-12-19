<?php

namespace App\Filament\Game\Resources\EntityResource\Pages;

use App\Filament\Game\Resources\EntityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEntities extends ListRecords
{
    protected static string $resource = EntityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
