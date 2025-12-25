<?php

namespace Tests\Feature;

use App\Models\Tv;
use App\Models\TvOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TvApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_tv_with_selected_options(): void
    {
        $option = TvOption::query()->create([
            'category' => 'SOUND',
            'code' => 'SOUNDBAR_BASIC',
            'label' => 'Basic Soundbar',
            'price_yen' => 19800,
            'constraints' => null,
        ]);

        $payload = [
            'name' => 'BRAVIA X90L',
            'maker' => 'SONY',
            'inch' => 55,
            'resolution' => '4K',
            'panel' => 'LCD',
            'hdmi_ports' => 4,
            'has_hdr' => true,
            'has_wifi' => true,
            'selected_options' => [
                [
                    'tv_option_id' => $option->id,
                    'quantity' => 2,
                ],
            ],
        ];

        $response = $this->postJson('/api/tvs', $payload);

        $response->assertCreated()
            ->assertJsonPath('name', 'BRAVIA X90L')
            ->assertJsonPath('selected_options.0.tv_option_id', $option->id)
            ->assertJsonPath('total_price_yen', 39600);
    }

    public function test_can_filter_tv_index_by_maker(): void
    {
        Tv::query()->create([
            'name' => 'BRAVIA X90L',
            'maker' => 'SONY',
            'inch' => 55,
            'resolution' => '4K',
            'panel' => 'LCD',
            'hdmi_ports' => 4,
            'has_hdr' => true,
            'has_wifi' => true,
        ]);
        Tv::query()->create([
            'name' => 'REGZA Z670',
            'maker' => 'TOSHIBA',
            'inch' => 50,
            'resolution' => '4K',
            'panel' => 'LCD',
            'hdmi_ports' => 3,
            'has_hdr' => true,
            'has_wifi' => true,
        ]);

        $response = $this->getJson('/api/tvs?maker=SONY');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.maker', 'SONY');
    }

    public function test_can_update_and_delete_tv(): void
    {
        $tv = Tv::query()->create([
            'name' => 'AQUOS',
            'maker' => 'SHARP',
            'inch' => 43,
            'resolution' => 'FullHD',
            'panel' => 'LCD',
            'hdmi_ports' => 2,
            'has_hdr' => false,
            'has_wifi' => false,
        ]);

        $update = [
            'name' => 'AQUOS Updated',
            'maker' => 'SHARP',
            'inch' => 50,
            'resolution' => 'FullHD',
            'panel' => 'LCD',
            'hdmi_ports' => 3,
            'has_hdr' => true,
            'has_wifi' => true,
        ];

        $this->putJson("/api/tvs/{$tv->id}", $update)
            ->assertOk()
            ->assertJsonPath('name', 'AQUOS Updated');

        $this->deleteJson("/api/tvs/{$tv->id}")
            ->assertNoContent();
    }
}
