<?php

namespace App\Observers;

use App\Models\Kecamatan;
use Illuminate\Support\Facades\Cache;

class KecamatanObserver
{
    public function created(Kecamatan $kecamatan): void
    {
        Cache::forget('api_kecamatan');
    }

    public function updated(Kecamatan $kecamatan): void
    {
        Cache::forget('api_kecamatan');
    }

    public function deleted(Kecamatan $kecamatan): void
    {
        Cache::forget('api_kecamatan');
    }
}
