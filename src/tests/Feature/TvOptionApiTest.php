<?php

namespace Tests\Feature;

use App\Models\TvOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TvOptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tv_option_index_can_filter_by_category(): void
    {
        TvOption::query()->create([
            'category' => 'SOUND',
            'code' => 'SOUNDBAR_BASIC',
            'label' => 'Basic Soundbar',
            'price_yen' => 19800,
            'constraints' => null,
        ]);
        TvOption::query()->create([
            'category' => 'WALL',
            'code' => 'WALL_MOUNT',
            'label' => 'Wall Mount Kit',
            'price_yen' => 12800,
            'constraints' => null,
        ]);

        $response = $this->getJson('/api/tv-options?category=SOUND');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.code', 'SOUNDBAR_BASIC');
    }

    public function test_tv_option_show_returns_single_option(): void
    {
        $option = TvOption::query()->create([
            'category' => 'STORAGE',
            'code' => 'USB_HDD_1TB',
            'label' => 'USB HDD 1TB',
            'price_yen' => 8800,
            'constraints' => null,
        ]);

        $response = $this->getJson("/api/tv-options/{$option->id}");

        $response->assertOk()
            ->assertJsonPath('id', $option->id)
            ->assertJsonPath('code', 'USB_HDD_1TB');
    }
}
