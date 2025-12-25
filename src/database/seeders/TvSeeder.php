<?php

namespace Database\Seeders;

use App\Models\Tv;
use App\Models\TvOption;
use App\Models\TvSelectedOption;
use Illuminate\Database\Seeder;

class TvSeeder extends Seeder
{
    public function run(): void
    {
        $tvs = [
            [
                'name' => 'BRAVIA X90L',
                'maker' => 'SONY',
                'inch' => 55,
                'resolution' => '4K',
                'panel' => 'LCD',
                'hdmi_ports' => 4,
                'has_hdr' => true,
                'has_wifi' => true,
                'options' => [
                    ['code' => 'SOUNDBAR_BASIC', 'quantity' => 1],
                    ['code' => 'WARRANTY_3Y', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'VIERA HX90',
                'maker' => 'Panasonic',
                'inch' => 65,
                'resolution' => '4K',
                'panel' => 'OLED',
                'hdmi_ports' => 4,
                'has_hdr' => true,
                'has_wifi' => true,
                'options' => [
                    ['code' => 'WALL_MOUNT', 'quantity' => 1],
                    ['code' => 'USB_HDD_1TB', 'quantity' => 1],
                ],
            ],
        ];

        foreach ($tvs as $payload) {
            $options = $payload['options'] ?? [];
            unset($payload['options']);

            $tv = Tv::query()->create($payload);

            foreach ($options as $option) {
                $tvOption = TvOption::query()->where('code', $option['code'])->first();
                if (! $tvOption) {
                    continue;
                }

                TvSelectedOption::query()->create([
                    'tv_id' => $tv->id,
                    'tv_option_id' => $tvOption->id,
                    'quantity' => $option['quantity'],
                ]);
            }
        }
    }
}
