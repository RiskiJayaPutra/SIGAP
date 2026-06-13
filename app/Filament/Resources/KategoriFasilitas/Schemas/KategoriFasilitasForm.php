<?php

namespace App\Filament\Resources\KategoriFasilitas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KategoriFasilitasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->required(),
                TextInput::make('icon'),
                TextInput::make('warna'),
            ]);
    }
}
