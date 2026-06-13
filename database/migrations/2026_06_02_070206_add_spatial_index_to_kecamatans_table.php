<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds a GiST spatial index on the geom column for fast ST_Contains lookups.
     */
    public function up(): void
    {
        // Create a functional GiST index on the geometry derived from the JSON geom column
        // This allows PostgreSQL to use the index for ST_Contains queries
        DB::statement("
            CREATE INDEX IF NOT EXISTS kecamatans_geom_gist_idx
            ON kecamatans
            USING GIST (ST_GeomFromGeoJSON(geom::text))
            WHERE geom IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS kecamatans_geom_gist_idx");
    }
};
