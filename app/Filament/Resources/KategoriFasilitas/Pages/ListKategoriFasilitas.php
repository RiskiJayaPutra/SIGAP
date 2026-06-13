<?php

namespace App\Filament\Resources\KategoriFasilitas\Pages;

use App\Filament\Resources\KategoriFasilitas\KategoriFasilitasResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKategoriFasilitas extends ListRecords
{
    protected static string $resource = KategoriFasilitasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
