<?php

namespace App\Filament\Resources\KategoriFasilitas\Pages;

use App\Filament\Resources\KategoriFasilitas\KategoriFasilitasResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKategoriFasilitas extends EditRecord
{
    protected static string $resource = KategoriFasilitasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
