<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\MangoVariety;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMangoVarietyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('mango_varieties', 'name')],
            'origin' => ['required', 'string', 'max:120'],
            'season' => ['required', 'string', 'max:60'],
            'season_start' => ['required', 'integer', 'between:1,12'],
            'season_end' => ['required', 'integer', 'between:1,12', 'gte:season_start'],
            'flavor' => ['required', 'string', 'max:1000'],
            'tags' => ['nullable', 'string', 'max:255'],
            'theme' => ['required', 'string', Rule::in(array_keys(MangoVariety::THEMES))],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        $data['tags'] = $this->parseTags($data['tags'] ?? null);

        return $data;
    }

    private function parseTags(?string $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        return collect(explode(',', $raw))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->values()
            ->all();
    }
}
