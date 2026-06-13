<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class KecamatanController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        // ✅ Cache only the raw array, NOT the JsonResponse object
        $result = Cache::remember('api_kecamatan', 3600, function () {
            $kecamatans = Kecamatan::whereNotNull('populasi')
                ->whereNotNull('luas_wilayah')
                ->withCount([
                    // Original 5 categories
                    'fasilitas as pendidikan_count'  => fn($q) => $q->whereHas('kategori', fn($q2) => $q2->where('nama', 'Pendidikan')),
                    'fasilitas as rumah_sakit_count'  => fn($q) => $q->whereHas('kategori', fn($q2) => $q2->where('nama', 'Rumah Sakit')),
                    'fasilitas as spbu_count'         => fn($q) => $q->whereHas('kategori', fn($q2) => $q2->where('nama', 'SPBU')),
                    'fasilitas as ibadah_count'       => fn($q) => $q->whereHas('kategori', fn($q2) => $q2->where('nama', 'Sarana Ibadah')),
                    'fasilitas as olahraga_count'     => fn($q) => $q->whereHas('kategori', fn($q2) => $q2->where('nama', 'Arena Olahraga')),
                    // New 5 categories
                    'fasilitas as klinik_count'       => fn($q) => $q->whereHas('kategori', fn($q2) => $q2->where('nama', 'Klinik')),
                    'fasilitas as posyandu_count'     => fn($q) => $q->whereHas('kategori', fn($q2) => $q2->where('nama', 'Posyandu')),
                    'fasilitas as tk_paud_count'      => fn($q) => $q->whereHas('kategori', fn($q2) => $q2->where('nama', 'TK / PAUD')),
                    'fasilitas as kuburan_count'      => fn($q) => $q->whereHas('kategori', fn($q2) => $q2->where('nama', 'Kuburan Umum')),
                    'fasilitas as wisata_count'       => fn($q) => $q->whereHas('kategori', fn($q2) => $q2->where('nama', 'Tempat Wisata')),
                ])
                ->get(['id', 'nama', 'populasi', 'luas_wilayah']);

            $data = [];

            foreach ($kecamatans as $kecamatan) {
                // All 10 category count keys — flat structure for easy JS access
                $countKeys = [
                    'pendidikan_count', 'rumah_sakit_count', 'spbu_count',
                    'ibadah_count', 'olahraga_count', 'klinik_count',
                    'posyandu_count', 'tk_paud_count', 'kuburan_count', 'wisata_count',
                ];

                // Build entry with flat count keys
                $entry = [
                    'id'           => $kecamatan->id,
                    'nama'         => $kecamatan->nama,
                    'populasi'     => (int) $kecamatan->populasi,
                    'luas_wilayah' => (float) $kecamatan->luas_wilayah,
                    'kepadatan'    => ($kecamatan->luas_wilayah > 0)
                        ? (int) round($kecamatan->populasi / $kecamatan->luas_wilayah)
                        : 0,
                ];

                // Add each count + compute total
                $total = 0;
                foreach ($countKeys as $key) {
                    $val = (int) $kecamatan->$key;
                    $entry[$key] = $val;
                    $total += $val;
                }
                $entry['total_fasilitas'] = $total;

                // Key by nama (must match GeoJSON NAMOBJ exactly)
                $data[$kecamatan->nama] = $entry;
            }

            return $data; // ✅ Return plain array — NOT response()->json()
        });

        return response()->json($result); // ✅ JsonResponse created OUTSIDE cache
    }
}
