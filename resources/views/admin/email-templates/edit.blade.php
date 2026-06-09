<x-admin-layout :title="'Edit '.$template->name" active="email-templates">
    <a
        href="{{ route('admin.email-templates.index') }}"
        class="inline-flex items-center gap-1 text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:text-stone-100 mb-3"
        data-testid="back-to-templates"
    >
        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/>
            <polyline points="12 19 5 12 12 5"/>
        </svg>
        All templates
    </a>

    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">{{ $template->name }}</h1>
        @if ($template->description)
            <p class="mt-1 text-stone-600 dark:text-stone-300 text-sm">{{ $template->description }}</p>
        @endif
        <p class="mt-2 text-xs text-stone-500 dark:text-stone-400 font-mono">{{ $template->key }}</p>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-6"
         x-data="{ subject: @js(old('subject', $template->subject)), body: @js(old('body', $template->body)) }">
        <div>
            {{-- Edit form: lives at PUT /update. The Preview button below
                 uses the HTML5 `form` attribute to submit the SEPARATE
                 preview form (sibling, not nested), bypassing this
                 form's `_method=PUT` spoofing. --}}
            <form method="POST" action="{{ route('admin.email-templates.update', $template) }}" class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="subject" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Subject</label>
                    
        <input id="subject" name="subject" type="text" required maxlength="255"
                           x-model="subject"
                           class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 font-mono text-sm"
    
                           data-testid="template-subject">
                    @error('subject') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="body" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Body</label>
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Markdown supported (<code>**bold**</code>, <code>[link](url)</code>). Separate paragraphs with a blank line — each paragraph becomes a line in the email.</p>
                    
        <textarea id="body" name="body" rows="14" required maxlength="5000"
                              x-model="body"
                              class="mt-2 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 font-mono text-sm"
    
                              data-testid="template-body"></textarea>
                    @error('body') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-stone-100 dark:border-stone-800">
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm font-medium" data-testid="save-template">Save template</button>
                    {{-- Targets the preview form (sibling below) via the
                         HTML5 `form` attribute, so PUT-spoofing on the
                         edit form doesn't get applied to this POST. --}}
                    <button type="submit"
                            form="template-preview-form"
                            class="inline-flex items-center px-4 py-2 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-800 dark:text-stone-200 hover:bg-stone-200 dark:hover:bg-stone-600 text-sm font-medium"
                            data-testid="preview-template">Preview ↗</button>
                    <a href="{{ route('admin.email-templates.index') }}" class="text-sm text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:text-stone-100">Cancel</a>
                </div>
            </form>

            {{-- Sibling form for preview. Hidden inputs mirror the live
                 subject/body via Alpine bindings so unsaved edits flow
                 through. `target="_blank"` opens the result in a new tab. --}}
            <form id="template-preview-form"
                  method="POST"
                  action="{{ route('admin.email-templates.preview', $template) }}"
                  target="_blank"
                  class="hidden">
                @csrf
                <input type="hidden" name="subject" :value="subject">
                <input type="hidden" name="body" :value="body">
            </form>
        </div>

        <aside class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-5 self-start sticky top-6">
            <h2 class="text-sm font-semibold text-stone-900 dark:text-stone-100 mb-2">Available placeholders</h2>
            @if (count($placeholders) === 0)
                <p class="text-xs text-stone-500 dark:text-stone-400">No placeholders documented for this template.</p>
            @else
                <p class="text-xs text-stone-500 dark:text-stone-400 mb-3">Wrap in curly braces, e.g. <code>{task_title}</code>. Unknown placeholders are left literal so typos are obvious in the rendered email.</p>
                <dl class="space-y-2.5" data-testid="placeholder-docs">
                    @foreach ($placeholders as $name => $description)
                        <div>
                            <dt><code class="text-xs bg-stone-100 dark:bg-stone-800 px-1.5 py-0.5 rounded text-stone-800 dark:text-stone-200">&#123;{{ $name }}&#125;</code></dt>
                            <dd class="mt-1 text-xs text-stone-600 dark:text-stone-300 leading-snug">{{ $description }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </aside>
    </div>
</x-admin-layout>
