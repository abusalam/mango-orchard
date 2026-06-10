{{-- Shared section form. Lives at /admin/mpcp/sections/create or /sections/{slug}/edit. --}}
@php
    $isEditing = isset($section) && $section->exists;
    $action = $isEditing
        ? route('admin.mpcp.sections.update', $section)
        : route('admin.mpcp.sections.store');
    $initialColumns = old('columns', $isEditing ? $section->columns : [
        ['key' => 'name', 'label_en' => 'Name', 'label_bn' => '', 'type' => 'text'],
    ]);
@endphp

<form method="POST" action="{{ $action }}" class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-6 space-y-5" data-testid="mpcp-section-form"
      x-data='{
          columns: @json($initialColumns),
          addColumn() {
              this.columns.push({key: "", label_en: "", label_bn: "", type: "text"});
          },
          removeColumn(i) {
              if (this.columns.length > 1) this.columns.splice(i, 1);
          }
      }'>
    @csrf
    @if ($isEditing) @method('PUT') @endif

    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label for="slug" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Slug</label>
            <input id="slug" name="slug" type="text" required pattern="[a-z0-9-]+"
                   value="{{ old('slug', $section->slug ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 font-mono text-sm">
            @error('slug') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="display_order" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Display order</label>
            <input id="display_order" name="display_order" type="number" min="0"
                   value="{{ old('display_order', $section->display_order ?? 0) }}"
                   class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100">
        </div>
        <div>
            <label for="layout" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Layout</label>
            <select id="layout" name="layout"
                    class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100">
                <option value="table" @selected(old('layout', $section->layout ?? 'table') === 'table')>Table (rows)</option>
                <option value="card" @selected(old('layout', $section->layout ?? '') === 'card')>Card (markdown bodies)</option>
            </select>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="title_en" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Title (English)</label>
            <input id="title_en" name="title_en" type="text" required value="{{ old('title_en', $section->title_en ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100">
        </div>
        <div>
            <label for="title_bn" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Title (Bengali)</label>
            <input id="title_bn" name="title_bn" type="text" value="{{ old('title_bn', $section->title_bn ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100">
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="intro_md_en" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Intro (EN, markdown)</label>
            <textarea id="intro_md_en" name="intro_md_en" rows="3"
                      class="mt-2 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 font-mono text-xs">{{ old('intro_md_en', $section->intro_md_en ?? '') }}</textarea>
        </div>
        <div>
            <label for="intro_md_bn" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Intro (BN, markdown)</label>
            <textarea id="intro_md_bn" name="intro_md_bn" rows="3"
                      class="mt-2 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 font-mono text-xs">{{ old('intro_md_bn', $section->intro_md_bn ?? '') }}</textarea>
        </div>
    </div>

    {{-- Columns repeater --}}
    <fieldset class="pt-4 border-t border-stone-100 dark:border-stone-800">
        <legend class="text-base font-semibold text-stone-900 dark:text-stone-100">Column schema</legend>
        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Each column defines one field on every entry in this section. <strong>Key</strong> is the JSON property name (snake_case, e.g. <code>mobile</code>); <strong>type</strong> drives both validation and rendering.</p>

        <template x-for="(col, i) in columns" :key="i">
            <div class="mt-3 grid sm:grid-cols-[1fr_1fr_1fr_140px_60px] gap-2 items-end p-3 rounded-xl border border-stone-200 dark:border-stone-700">
                <div>
                    <label :for="'col_key_' + i" class="block text-xs font-medium text-stone-700 dark:text-stone-300">Key</label>
                    <input :id="'col_key_' + i" :name="'columns[' + i + '][key]'" x-model="col.key" type="text" required pattern="[a-z0-9_]+"
                           class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 font-mono text-xs">
                </div>
                <div>
                    <label :for="'col_label_en_' + i" class="block text-xs font-medium text-stone-700 dark:text-stone-300">Label (EN)</label>
                    <input :id="'col_label_en_' + i" :name="'columns[' + i + '][label_en]'" x-model="col.label_en" type="text" required
                           class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 text-sm">
                </div>
                <div>
                    <label :for="'col_label_bn_' + i" class="block text-xs font-medium text-stone-700 dark:text-stone-300">Label (BN)</label>
                    <input :id="'col_label_bn_' + i" :name="'columns[' + i + '][label_bn]'" x-model="col.label_bn" type="text"
                           class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 text-sm">
                </div>
                <div>
                    <label :for="'col_type_' + i" class="block text-xs font-medium text-stone-700 dark:text-stone-300">Type</label>
                    <select :id="'col_type_' + i" :name="'columns[' + i + '][type]'" x-model="col.type"
                            class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 text-sm">
                        <option value="text">text</option>
                        <option value="tel">tel</option>
                        <option value="email">email</option>
                        <option value="long_text">long_text</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="button" @click="removeColumn(i)" x-show="columns.length > 1"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-full text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-stone-800"
                            aria-label="Remove column">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 5l10 10M15 5l-10 10" stroke-linecap="round"/></svg>
                    </button>
                </div>
            </div>
        </template>
        <button type="button" @click="addColumn()" class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 text-sm hover:border-stone-400" data-testid="add-column">
            + Add column
        </button>
    </fieldset>

    <label class="flex items-start gap-3 p-3 rounded-xl border border-stone-200 dark:border-stone-700 cursor-pointer hover:border-orange-300 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-950 transition-colors">
        <input type="hidden" name="published" value="0">
        <input type="checkbox" name="published" value="1" @checked(old('published', $section->published ?? true)) class="mt-1 rounded text-emerald-500 focus:ring-emerald-400">
        <span>
            <span class="block font-medium text-stone-900 dark:text-stone-100">Published</span>
            <span class="block text-xs text-stone-500 dark:text-stone-400 mt-0.5">When off, the section is hidden from the public /mpcp page (still visible in admin).</span>
        </span>
    </label>

    <div class="flex items-center gap-3 pt-2 border-t border-stone-100 dark:border-stone-800">
        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm font-medium" data-testid="save-section">{{ $isEditing ? 'Save section' : 'Create section' }}</button>
        <a href="{{ route('admin.mpcp.index') }}" class="text-sm text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">Cancel</a>
    </div>
</form>
