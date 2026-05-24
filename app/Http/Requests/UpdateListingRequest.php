<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Listing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Listing|null $listing */
        $listing = $this->route('listing');
        return $listing !== null && ($this->user()?->can('update', $listing) ?? false);
    }

    public function rules(): array
    {
        return [
            'mango_variety_id' => ['required', Rule::exists('mango_varieties', 'id')],
            'farm_name' => ['required', 'string', 'max:120'],
            'location' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'availability_start_month' => ['required', 'integer', 'between:1,12'],
            'availability_end_month' => ['required', 'integer', 'between:1,12', 'gte:availability_start_month'],
            'price_per_kg' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'quantity_available_kg' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(array_keys(Listing::STATUSES))],
        ];
    }
}
