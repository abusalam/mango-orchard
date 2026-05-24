@props(['listing', 'varieties', 'action', 'method' => 'POST'])

@php
    $months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <div>
        <label for="mango_variety_id" class="block text-sm font-medium text-stone-800">Mango variety</label>
        <select name="mango_variety_id" id="mango_variety_id" required
                class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            <option value="">— Pick a variety —</option>
            @foreach ($varieties as $variety)
                <option value="{{ $variety->id }}" @selected((int) old('mango_variety_id', $listing->mango_variety_id) === $variety->id)>
                    {{ $variety->name }} — {{ $variety->origin }}
                </option>
            @endforeach
        </select>
        @error('mango_variety_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="farm_name" class="block text-sm font-medium text-stone-800">Farm / orchard name</label>
            <input type="text" name="farm_name" id="farm_name" required maxlength="120"
                   value="{{ old('farm_name', $listing->farm_name) }}"
                   placeholder="Sunrise Orchards"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('farm_name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="location" class="block text-sm font-medium text-stone-800">Location</label>
            <input type="text" name="location" id="location" required maxlength="120"
                   value="{{ old('location', $listing->location) }}"
                   placeholder="Ratnagiri, Maharashtra"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('location') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-stone-800">Your story <span class="text-stone-400 font-normal">(optional)</span></label>
        <textarea name="description" id="description" rows="4" maxlength="2000"
                  placeholder="Tell buyers about your orchard, harvest practices, certifications…"
                  class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">{{ old('description', $listing->description) }}</textarea>
        @error('description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="availability_start_month" class="block text-sm font-medium text-stone-800">Available from</label>
            <select name="availability_start_month" id="availability_start_month" required
                    class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected((int) old('availability_start_month', $listing->availability_start_month) === $m)>{{ $months[$m] }}</option>
                @endforeach
            </select>
            @error('availability_start_month') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="availability_end_month" class="block text-sm font-medium text-stone-800">Available until</label>
            <select name="availability_end_month" id="availability_end_month" required
                    class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected((int) old('availability_end_month', $listing->availability_end_month) === $m)>{{ $months[$m] }}</option>
                @endforeach
            </select>
            @error('availability_end_month') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="price_per_kg" class="block text-sm font-medium text-stone-800">Price per kg <span class="text-stone-400 font-normal">(optional)</span></label>
            <input type="number" name="price_per_kg" id="price_per_kg" min="0" max="999999" step="0.01"
                   value="{{ old('price_per_kg', $listing->price_per_kg) }}"
                   placeholder="450.00"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('price_per_kg') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="quantity_available_kg" class="block text-sm font-medium text-stone-800">Quantity (kg) <span class="text-stone-400 font-normal">(optional)</span></label>
            <input type="number" name="quantity_available_kg" id="quantity_available_kg" min="0" max="1000000" step="1"
                   value="{{ old('quantity_available_kg', $listing->quantity_available_kg) }}"
                   placeholder="500"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('quantity_available_kg') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="contact_email" class="block text-sm font-medium text-stone-800">Contact email <span class="text-stone-400 font-normal">(shown to buyers)</span></label>
            <input type="email" name="contact_email" id="contact_email" maxlength="255"
                   value="{{ old('contact_email', $listing->contact_email) }}"
                   placeholder="orders@yourfarm.example"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('contact_email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="contact_phone" class="block text-sm font-medium text-stone-800">Contact phone <span class="text-stone-400 font-normal">(optional)</span></label>
            <input type="text" name="contact_phone" id="contact_phone" maxlength="40"
                   value="{{ old('contact_phone', $listing->contact_phone) }}"
                   placeholder="+91 98765 43210"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('contact_phone') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <fieldset>
        <legend class="block text-sm font-medium text-stone-800">Visibility</legend>
        <div class="mt-3 space-y-2">
            @foreach (\App\Models\Listing::STATUSES as $value => $label)
                <label class="flex items-start gap-3 p-3 rounded-xl border border-stone-200 cursor-pointer hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 transition-colors">
                    <input type="radio" name="status" value="{{ $value }}" required
                           @checked(old('status', $listing->status) === $value)
                           class="mt-1 text-orange-500 focus:ring-orange-400">
                    <span class="text-sm text-stone-800">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @error('status') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </fieldset>

    <div class="flex items-center gap-3 pt-4 border-t border-stone-100">
        <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
            {{ $slot ?? 'Save listing' }}
        </button>
        <a href="{{ route('my.listings.index') }}" class="text-sm text-stone-600 hover:text-stone-900">Cancel</a>
    </div>
</form>
