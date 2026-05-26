@props(['event', 'action', 'method' => 'POST'])

@php
    $toDatetimeLocal = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i') : '';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="title" class="block text-sm font-medium text-stone-800">Title</label>
        <input type="text" name="title" id="title" required maxlength="180"
               value="{{ old('title', $event->title) }}"
               placeholder="e.g. Pre-monsoon Pruning Workshop"
               class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
        @error('title') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-stone-800">Description</label>
        <textarea name="description" id="description" rows="6" required maxlength="5000"
                  placeholder="Who it's for, what attendees will learn, what to bring, etc."
                  class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">{{ old('description', $event->description) }}</textarea>
        @error('description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="start_at" class="block text-sm font-medium text-stone-800">Starts at</label>
            <input type="datetime-local" name="start_at" id="start_at" required
                   value="{{ old('start_at', $toDatetimeLocal($event->start_at)) }}"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('start_at') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="end_at" class="block text-sm font-medium text-stone-800">Ends at <span class="text-stone-400 font-normal">(optional)</span></label>
            <input type="datetime-local" name="end_at" id="end_at"
                   value="{{ old('end_at', $toDatetimeLocal($event->end_at)) }}"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('end_at') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="location" class="block text-sm font-medium text-stone-800">Location</label>
            <input type="text" name="location" id="location" required maxlength="180"
                   value="{{ old('location', $event->location) }}"
                   placeholder="e.g. KVK Ratnagiri  ·  or  ·  Online"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('location') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="location_url" class="block text-sm font-medium text-stone-800">Location URL <span class="text-stone-400 font-normal">(map / meeting link)</span></label>
            <input type="url" name="location_url" id="location_url" maxlength="500"
                   value="{{ old('location_url', $event->location_url) }}"
                   placeholder="https://maps.google.com/?q=…"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('location_url') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="host" class="block text-sm font-medium text-stone-800">Host / organiser <span class="text-stone-400 font-normal">(optional)</span></label>
            <input type="text" name="host" id="host" maxlength="180"
                   value="{{ old('host', $event->host) }}"
                   placeholder="e.g. Konkan Krishi Vidyapeeth"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('host') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="capacity" class="block text-sm font-medium text-stone-800">Capacity <span class="text-stone-400 font-normal">(optional)</span></label>
            <input type="number" name="capacity" id="capacity" min="1" max="100000"
                   value="{{ old('capacity', $event->capacity) }}"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('capacity') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="registration_url" class="block text-sm font-medium text-stone-800">External registration URL <span class="text-stone-400 font-normal">(optional)</span></label>
        <input type="url" name="registration_url" id="registration_url" maxlength="500"
               value="{{ old('registration_url', $event->registration_url) }}"
               placeholder="https://forms.google.com/…"
               class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
        <p class="mt-1 text-xs text-stone-500">If you collect signups elsewhere, paste the link here. The event page will show a "Register" button pointing to it.</p>
        @error('registration_url') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-stone-800">Status</label>
        <select name="status" id="status" required
                class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @foreach (\App\Models\Event::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $event->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-3 pt-4 border-t border-stone-100">
        <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
            {{ $slot ?? 'Save event' }}
        </button>
        <a href="{{ route('admin.events.index') }}" class="text-sm text-stone-600 hover:text-stone-900">Cancel</a>
    </div>
</form>
