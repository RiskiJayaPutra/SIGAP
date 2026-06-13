<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateMarkerIcons extends Command
{
    protected $signature = 'sigap:generate-icons {--force : Overwrite existing icons}';
    protected $description = 'Generate PNG marker icons for 5 new facility categories via PHP GD';

    public function handle(): int
    {
        if (!extension_loaded('gd')) {
            $this->error('PHP GD extension is not loaded. Please enable it in php.ini.');
            return Command::FAILURE;
        }

        // Check existing icon size to match
        $referenceIcon = public_path('images/hospital.png');
        $size = 32; // default fallback
        if (file_exists($referenceIcon)) {
            $info = getimagesize($referenceIcon);
            if ($info) {
                $size = $info[0]; // use width of existing icon
                $this->info("Detected existing icon size: {$size}x{$size}");
            }
        }

        $force = $this->option('force');

        $icons = [
            [
                'filename' => 'clinic.png',
                'label'    => 'Klinik Umum',
                'bg'       => [0x06, 0xB6, 0xD4], // cyan
                'draw'     => function ($img, $white, $size) {
                    // Medical cross (plus sign) centered
                    $cx = (int)($size / 2);
                    $cy = (int)($size / 2);
                    $barW = (int)($size * 0.12); // half-width of bar
                    $barL = (int)($size * 0.28); // half-length of bar
                    // Vertical bar
                    imagefilledrectangle($img, $cx - $barW, $cy - $barL, $cx + $barW, $cy + $barL, $white);
                    // Horizontal bar
                    imagefilledrectangle($img, $cx - $barL, $cy - $barW, $cx + $barL, $cy + $barW, $white);
                },
            ],
            [
                'filename' => 'posyandu.png',
                'label'    => 'Posyandu',
                'bg'       => [0xEC, 0x48, 0x99], // pink
                'draw'     => function ($img, $white, $size) {
                    // Heart shape: two ellipses + bottom triangle
                    $cx = (int)($size / 2);
                    $cy = (int)($size / 2);
                    $r = (int)($size * 0.15);
                    // Left lobe
                    imagefilledellipse($img, $cx - $r, $cy - (int)($r * 0.4), $r * 2, $r * 2, $white);
                    // Right lobe
                    imagefilledellipse($img, $cx + $r, $cy - (int)($r * 0.4), $r * 2, $r * 2, $white);
                    // Bottom triangle
                    $points = [
                        $cx - (int)($r * 2.0), $cy,       // left
                        $cx + (int)($r * 2.0), $cy,       // right
                        $cx, $cy + (int)($r * 2.2),       // bottom tip
                    ];
                    imagefilledpolygon($img, $points, $white);
                },
            ],
            [
                'filename' => 'kindergarten.png',
                'label'    => 'TK / PAUD',
                'bg'       => [0xF9, 0x73, 0x16], // orange
                'draw'     => function ($img, $white, $size) {
                    // 5-point star
                    $cx = (int)($size / 2);
                    $cy = (int)($size / 2);
                    $outerR = (int)($size * 0.30);
                    $innerR = (int)($size * 0.13);
                    $points = [];
                    for ($i = 0; $i < 10; $i++) {
                        $angle = deg2rad(-90 + $i * 36);
                        $r = ($i % 2 === 0) ? $outerR : $innerR;
                        $points[] = (int)($cx + $r * cos($angle));
                        $points[] = (int)($cy + $r * sin($angle));
                    }
                    imagefilledpolygon($img, $points, $white);
                },
            ],
            [
                'filename' => 'cemetery.png',
                'label'    => 'Kuburan Umum',
                'bg'       => [0x6B, 0x72, 0x80], // gray
                'draw'     => function ($img, $white, $size) {
                    // Grave cross — taller vertical, shorter horizontal near top
                    $cx = (int)($size / 2);
                    $barW = (int)($size * 0.07);
                    $topY = (int)($size * 0.22);
                    $botY = (int)($size * 0.78);
                    // Vertical bar
                    imagefilledrectangle($img, $cx - $barW, $topY, $cx + $barW, $botY, $white);
                    // Horizontal bar near top
                    $crossY = (int)($size * 0.36);
                    $crossW = (int)($size * 0.20);
                    imagefilledrectangle($img, $cx - $crossW, $crossY - $barW, $cx + $crossW, $crossY + $barW, $white);
                },
            ],
            [
                'filename' => 'tourism.png',
                'label'    => 'Tempat Wisata',
                'bg'       => [0x84, 0xCC, 0x16], // lime green
                'draw'     => function ($img, $white, $size) {
                    // Flag on a pole
                    $poleX = (int)($size * 0.35);
                    $poleW = (int)(max(1, $size * 0.04));
                    $topY = (int)($size * 0.22);
                    $botY = (int)($size * 0.78);
                    // Pole
                    imagefilledrectangle($img, $poleX - $poleW, $topY, $poleX + $poleW, $botY, $white);
                    // Triangular flag to the right of the pole
                    $flagH = (int)($size * 0.22);
                    $flagW = (int)($size * 0.28);
                    $flagPoints = [
                        $poleX + $poleW + 1, $topY,                  // top-left of flag
                        $poleX + $poleW + $flagW, $topY + (int)($flagH / 2), // right tip
                        $poleX + $poleW + 1, $topY + $flagH,         // bottom-left of flag
                    ];
                    imagefilledpolygon($img, $flagPoints, $white);
                },
            ],
        ];

        foreach ($icons as $iconDef) {
            $outputPath = public_path('images/' . $iconDef['filename']);

            if (file_exists($outputPath) && !$force) {
                $this->warn("Skipping {$iconDef['filename']} — already exists (use --force to overwrite)");
                continue;
            }

            $this->output->write("Generating {$iconDef['filename']}... ");

            $img = imagecreatetruecolor($size, $size);

            // Enable transparency
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefill($img, 0, 0, $transparent);
            imagealphablending($img, true);

            // Background circle
            $bgColor = imagecolorallocate($img, $iconDef['bg'][0], $iconDef['bg'][1], $iconDef['bg'][2]);
            $circleD = (int)($size * 0.94);
            imagefilledellipse($img, (int)($size / 2), (int)($size / 2), $circleD, $circleD, $bgColor);

            // White symbol
            $white = imagecolorallocate($img, 255, 255, 255);
            ($iconDef['draw'])($img, $white, $size);

            // Save
            imagepng($img, $outputPath);
            imagedestroy($img);

            $this->info("✓ saved to public/images/{$iconDef['filename']}");
        }

        $this->newLine();
        $this->info('✓ All 5 icons generated successfully.');

        return Command::SUCCESS;
    }
}
