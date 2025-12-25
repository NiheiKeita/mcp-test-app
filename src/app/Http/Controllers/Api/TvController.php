<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tv;
use App\Models\TvSelectedOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TvController extends Controller
{
    private const MAKERS = [
        'SONY',
        'Panasonic',
        'TOSHIBA',
        'SHARP',
        'LG',
        'SAMSUNG',
    ];

    private const RESOLUTIONS = [
        'HD',
        'FullHD',
        '4K',
        '8K',
    ];

    private const PANELS = [
        'LCD',
        'OLED',
        'MiniLED',
    ];

    private const INCHES = [
        32,
        43,
        50,
        55,
        65,
        75,
    ];

    public function index(Request $request): JsonResponse
    {
        $query = Tv::query();

        if ($request->filled('maker')) {
            $request->validate([
                'maker' => ['string', Rule::in(self::MAKERS)],
            ]);
            $query->where('maker', $request->string('maker')->toString());
        }

        if ($request->filled('resolution')) {
            $request->validate([
                'resolution' => ['string', Rule::in(self::RESOLUTIONS)],
            ]);
            $query->where('resolution', $request->string('resolution')->toString());
        }

        if ($request->filled('panel')) {
            $request->validate([
                'panel' => ['string', Rule::in(self::PANELS)],
            ]);
            $query->where('panel', $request->string('panel')->toString());
        }

        if ($request->filled('has_hdr')) {
            $request->validate([
                'has_hdr' => ['boolean'],
            ]);
            $query->where('has_hdr', $request->boolean('has_hdr'));
        }

        return response()->json(
            $query->with('selectedOptions.tvOption')->orderBy('id')->get()
                ->map(fn (Tv $tv) => $this->formatTv($tv))
        );
    }

    public function show(Tv $tv): JsonResponse
    {
        $tv->load('selectedOptions.tvOption');

        return response()->json($this->formatTv($tv));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        return DB::transaction(function () use ($validated): JsonResponse {
            $tv = Tv::create(Arr::except($validated, ['selected_options']));

            if (array_key_exists('selected_options', $validated)) {
                $this->syncSelectedOptions($tv, $validated['selected_options']);
            }

            $tv->load('selectedOptions.tvOption');

            return response()->json($this->formatTv($tv), 201);
        });
    }

    public function update(Request $request, Tv $tv): JsonResponse
    {
        $validated = $request->validate($this->rules(true));

        return DB::transaction(function () use ($validated, $tv): JsonResponse {
            $tv->fill(Arr::except($validated, ['selected_options']));
            $tv->save();

            if (array_key_exists('selected_options', $validated)) {
                $this->syncSelectedOptions($tv, $validated['selected_options']);
            }

            $tv->load('selectedOptions.tvOption');

            return response()->json($this->formatTv($tv));
        });
    }

    public function destroy(Tv $tv): JsonResponse
    {
        $tv->delete();

        return response()->json(null, 204);
    }

    private function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:255'],
            'maker' => [$required, 'string', Rule::in(self::MAKERS)],
            'inch' => [$required, 'integer', Rule::in(self::INCHES)],
            'resolution' => [$required, 'string', Rule::in(self::RESOLUTIONS)],
            'panel' => [$required, 'string', Rule::in(self::PANELS)],
            'hdmi_ports' => [$required, 'integer', 'min:1', 'max:6'],
            'has_hdr' => [$required, 'boolean'],
            'has_wifi' => [$required, 'boolean'],
            'selected_options' => ['sometimes', 'array'],
            'selected_options.*.tv_option_id' => ['required_with:selected_options', 'integer', 'exists:tv_options,id'],
            'selected_options.*.quantity' => ['required_with:selected_options', 'integer', 'min:1', 'max:5'],
        ];
    }

    private function syncSelectedOptions(Tv $tv, array $selectedOptions): void
    {
        $tv->selectedOptions()->delete();

        $payload = array_map(
            fn (array $item): array => [
                'tv_option_id' => $item['tv_option_id'],
                'quantity' => $item['quantity'],
            ],
            $selectedOptions
        );

        $tv->selectedOptions()->createMany($payload);
    }

    private function formatTv(Tv $tv): array
    {
        $selectedOptions = $tv->selectedOptions->map(
            fn (TvSelectedOption $selected): array => [
                'id' => $selected->id,
                'tv_option_id' => $selected->tv_option_id,
                'quantity' => $selected->quantity,
                'tv_option' => $selected->tvOption,
            ]
        )->values();

        $total = $tv->selectedOptions->sum(
            fn (TvSelectedOption $selected): int => (int) $selected->quantity * (int) $selected->tvOption?->price_yen
        );

        return array_merge($tv->toArray(), [
            'selected_options' => $selectedOptions,
            'total_price_yen' => $total,
        ]);
    }
}
