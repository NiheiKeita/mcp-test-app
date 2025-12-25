<?php

namespace Database\Seeders;

use App\Models\TvOption;
use Illuminate\Database\Seeder;

class TvOptionSeeder extends Seeder
{
    public function run(): void
    {
        $options = [
            [
                'category' => 'SOUND',
                'code' => 'SOUNDBAR_BASIC',
                'label' => 'Basic Soundbar',
                'price_yen' => 19800,
                'constraints' => null,
            ],
            [
                'category' => 'SOUND',
                'code' => 'SOUNDBAR_PREMIUM',
                'label' => 'Premium Soundbar',
                'price_yen' => 49800,
                'constraints' => ['resolution_min' => '4K'],
            ],
            [
                'category' => 'WALL',
                'code' => 'WALL_MOUNT',
                'label' => 'Wall Mount Kit',
                'price_yen' => 12800,
                'constraints' => null,
            ],
            [
                'category' => 'STORAGE',
                'code' => 'USB_HDD_1TB',
                'label' => 'USB HDD 1TB',
                'price_yen' => 8800,
                'constraints' => null,
            ],
            [
                'category' => 'STORAGE',
                'code' => 'USB_HDD_2TB',
                'label' => 'USB HDD 2TB',
                'price_yen' => 12800,
                'constraints' => null,
            ],
            [
                'category' => 'EXTENDED_WARRANTY',
                'code' => 'WARRANTY_3Y',
                'label' => 'Extended Warranty 3 Years',
                'price_yen' => 15800,
                'constraints' => null,
            ],
            [
                'category' => 'EXTENDED_WARRANTY',
                'code' => 'WARRANTY_5Y',
                'label' => 'Extended Warranty 5 Years',
                'price_yen' => 22800,
                'constraints' => null,
            ],
        ];

        foreach ($options as $option) {
            TvOption::query()->updateOrCreate(
                ['code' => $option['code']],
                $option
            );
        }
    }
}
