<x-admin-layout title="Email templates" active="email-templates">
    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">Email templates</h1>
        <p class="mt-1 text-stone-600 text-sm">Edit the wording of automated emails sent by the platform. Placeholders like <code>{task_title}</code> are substituted at send time.</p>
    </header>

    @if ($groups->isEmpty())
        <div class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800" data-testid="email-templates-empty">
            <p class="px-6 py-12 text-center text-stone-500 text-sm">No templates seeded yet. Run <code>sail artisan db:seed --class=EmailTemplateSeeder</code>.</p>
        </div>
    @else
        @foreach ($groups as $moduleName => $templates)
            @php($slug = \Illuminate\Support\Str::slug($moduleName))
            <section class="mb-10" data-testid="email-templates-group-{{ $slug }}">
                <div class="flex items-end justify-between mb-3">
                    <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">{{ $moduleName }}</h2>
                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ $templates->count() }} {{ Str::plural('template', $templates->count()) }}</p>
                </div>
                <div class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800">
                    <table class="w-full text-sm">
                        <thead class="bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 text-left">
                            <tr>
                                <th class="px-4 py-2 font-medium">Template</th>
                                <th class="px-4 py-2 font-medium hidden md:table-cell">Subject</th>
                                <th class="px-4 py-2 font-medium hidden lg:table-cell">Updated</th>
                                <th class="px-4 py-2 font-medium text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                            @foreach ($templates as $template)
                                <tr class="odd:bg-white dark:odd:bg-stone-950 even:bg-stone-50/50 dark:even:bg-stone-900" data-testid="email-template-row-{{ $template->id }}">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-stone-900 dark:text-stone-100">{{ $template->name }}</p>
                                        <p class="text-xs text-stone-500 dark:text-stone-400 font-mono mt-0.5">{{ $template->key }}</p>
                                        @if ($template->description)
                                            <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">{{ $template->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-stone-600 dark:text-stone-300 hidden md:table-cell break-words max-w-md">
                                        <code>{{ $template->subject }}</code>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-stone-500 dark:text-stone-400 hidden lg:table-cell whitespace-nowrap">
                                        {{ $template->updated_at?->diffForHumans() ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                        <a href="{{ route('admin.email-templates.preview', $template) }}"
                                           target="_blank"
                                           class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-800 dark:text-stone-200 hover:bg-stone-200 dark:hover:bg-stone-600 text-xs font-medium"
                                           data-testid="preview-link-{{ $template->id }}">Preview ↗</a>
                                        <a href="{{ route('admin.email-templates.edit', $template) }}"
                                           class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-xs font-medium"
                                           data-testid="edit-template-{{ $template->id }}">Edit</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    @endif
</x-admin-layout>
