@props([
    'name' => 'designation_ids[]',
    'options' => [],
    'selected' => [],
    'placeholder' => 'Search designations…',
    'form' => null,
])

@php
    // Normalise option list to a stable [{id, name}] shape so the JS picker
    // doesn't need to know about Eloquent collections.
    $optionsPayload = collect($options)->map(fn ($o) => [
        'id' => (int) (is_array($o) ? $o['id'] : $o->id),
        'name' => (string) (is_array($o) ? $o['name'] : $o->name),
    ])->values()->all();
    $selectedPayload = collect($selected)->map(fn ($id) => (int) $id)->values()->all();
@endphp

<div
    x-data="{
        open: false,
        search: '',
        selectedIds: @js($selectedPayload),
        options: @js($optionsPayload),
        get available() {
            const needle = this.search.trim().toLowerCase();
            return this.options.filter(o =>
                !this.selectedIds.includes(o.id)
                && (needle === '' || o.name.toLowerCase().includes(needle))
            );
        },
        get selectedOptions() {
            return this.selectedIds
                .map(id => this.options.find(o => o.id === id))
                .filter(Boolean);
        },
        toggle(id) {
            if (this.selectedIds.includes(id)) {
                this.selectedIds = this.selectedIds.filter(x => x !== id);
            } else {
                this.selectedIds = [...this.selectedIds, id];
            }
            this.search = '';
            this.$refs.search?.focus();
        },
        remove(id) {
            this.selectedIds = this.selectedIds.filter(x => x !== id);
        },
    }"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
    class="relative inline-block w-full max-w-xs"
    :class="open ? 'z-40' : 'z-10'"
    data-testid="designation-picker"
>
    {{-- Hidden inputs that mirror selectedIds — one per id so the form posts
         designation_ids[] in array form. --}}
    <template x-for="id in selectedIds" :key="id">
        <input type="hidden" :name="@js($name)" :value="id" @if ($form) form="{{ $form }}" @endif>
    </template>

    {{-- Trigger: chips + search input wrap together inside the bordered
         control, so what's picked is always visible. --}}
    <div
        @click="open = true; $refs.search?.focus()"
        class="min-h-[2.25rem] flex flex-wrap items-center gap-1 px-2 py-1 rounded-lg border border-stone-300 bg-white text-xs cursor-text focus-within:border-orange-400 focus-within:ring-1 focus-within:ring-orange-400"
    >
        <template x-for="opt in selectedOptions" :key="opt.id">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-stone-900 text-amber-50">
                <span x-text="opt.name"></span>
                <button
                    type="button"
                    @click.stop="remove(opt.id)"
                    class="leading-none text-amber-50/80 hover:text-amber-50 focus:outline-none"
                    aria-label="Remove"
                >&times;</button>
            </span>
        </template>
        <input
            x-ref="search"
            type="text"
            x-model="search"
            @focus="open = true"
            @keydown.backspace="search === '' && selectedIds.length > 0 && remove(selectedIds[selectedIds.length - 1])"
            :placeholder="selectedIds.length === 0 ? @js($placeholder) : ''"
            class="flex-1 min-w-[6rem] border-0 p-0 text-xs focus:ring-0 bg-transparent"
            data-testid="designation-picker-search"
        >
        <svg
            class="shrink-0 w-3 h-3 text-stone-400 transition-transform"
            :class="open ? 'rotate-180' : ''"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
        >
            <polyline points="6 9 12 15 18 9"/>
        </svg>
    </div>

    {{-- Dropdown of unselected matches. Chips live in the trigger, so the
         panel only lists what's still available. --}}
    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.100ms
        class="absolute left-0 right-0 mt-1 max-h-48 overflow-y-auto rounded-lg border border-stone-200 bg-white shadow-lg z-30"
    >
        <template x-if="available.length === 0">
            <p class="px-3 py-2 text-xs text-stone-500" x-text="search.trim() === '' ? 'No more designations.' : 'No matches.'"></p>
        </template>
        <template x-for="opt in available" :key="opt.id">
            <button
                type="button"
                @click="toggle(opt.id)"
                class="block w-full text-left px-3 py-1.5 text-xs hover:bg-stone-100"
                x-text="opt.name"
            ></button>
        </template>
    </div>
</div>
