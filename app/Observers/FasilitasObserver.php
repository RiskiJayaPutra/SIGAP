<?php

namespace App\Observers;

use App\Models\Fasilitas;
use Illuminate\Support\Facades\Cache;

class FasilitasObserver
{
    public function created(Fasilitas $fasilitas): void
    {
        Cache::forget('api_kecamatan');
    }

    public function updated(Fasilitas $fasilitas): void
    {
        Cache::forget('api_kecamatan');
    }

    public function deleted(Fasilitas $fasilitas): void
    {
        Cache::forget('api_kecamatan');
    }
}
