<?php

namespace App\Filament\Resources\Kecamatans\Pages;

use App\Filament\Resources\Kecamatans\KecamatanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKecamatans extends ListRecords
{
    protected static string $resource = KecamatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
