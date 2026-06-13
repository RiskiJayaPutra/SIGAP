<?php

namespace App\Filament\Resources\Kecamatans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KecamatanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->required(),
                TextInput::make('populasi')
                    ->numeric(),
                TextInput::make('luas_wilayah')
                    ->numeric(),
                \Filament\Forms\Components\Textarea::make('geom')
                    ->label('Geometri (GeoJSON Polygon/MultiPolygon)')
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                if (empty($value)) return;
                                
                                $decoded = json_decode($value, true);
                                if (json_last_error() !== JSON_ERROR_NONE) {
                                    $fail('Data tidak valid. Harus berformat JSON yang sah.');
                                    return;
                                }
                                
                                if (!isset($decoded['type']) || !in_array($decoded['type'], ['Polygon', 'MultiPolygon', 'Feature'])) {
                                    $fail('Data JSON harus memiliki type "Polygon", "MultiPolygon", atau "Feature".');
                                }
                            };
                        },
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
