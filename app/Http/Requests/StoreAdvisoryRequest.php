<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Modules\MangoOrchard\Models\Advisory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdvisoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(\App\Permissions::ADVISORIES_MANAGE) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
            'category' => ['required', 'string', Rule::in(array_keys(Advisory::CATEGORIES))],
            'severity' => ['required', 'string', Rule::in(array_keys(Advisory::SEVERITIES))],
            // mango_variety_ids is the multi-select pivot. Empty array means
            // the advisory applies to every variety (general guidance).
            'mango_variety_ids' => ['nullable', 'array'],
            'mango_variety_ids.*' => ['integer', Rule::exists('mango_varieties', 'id')],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:issued_at'],
            'published' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'published' => $this->boolean('published'),
            'remove_image' => $this->boolean('remove_image'),
            'mango_variety_ids' => $this->input('mango_variety_ids', []),
        ]);
    }
}
