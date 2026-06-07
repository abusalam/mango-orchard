<x-admin-layout title="Email templates" active="email-templates">
    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">Email templates</h1>
        <p class="mt-1 text-stone-600 text-sm">Edit the wording of automated emails sent by the platform. Placeholders like <code>{task_title}</code> are substituted at send time.</p>
    </header>

    @if ($groups->isEmpty())
        <div class="bg-white rounded-2xl border border-stone-200" data-testid="email-templates-empty">
            <p class="px-6 py-12 text-center text-stone-500 text-sm">No templates seeded yet. Run <code>sail artisan db:seed --class=EmailTemplateSeeder</code>.</p>
        </div>
    @else
        @foreach ($groups as $moduleName => $templates)
            @php($slug = \Illuminate\Support\Str::slug($moduleName))
            <section class="mb-10" data-testid="email-templates-group-{{ $slug }}">
                <div class="flex items-end justify-between mb-3">
                    <h2 class="text-lg font-semibold text-stone-900">{{ $moduleName }}</h2>
                    <p class="text-xs text-stone-500">{{ $templates->count() }} {{ Str::plural('template', $templates->count()) }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-stone-200">
                    <table class="w-full text-sm">
                        <thead class="bg-stone-50 text-stone-500 text-left">
                            <tr>
                                <th class="px-4 py-2 font-medium">Template</th>
                                <th class="px-4 py-2 font-medium hidden md:table-cell">Subject</th>
                                <th class="px-4 py-2 font-medium hidden lg:table-cell">Updated</th>
                                <th class="px-4 py-2 font-medium text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($templates as $template)
                                <tr class="odd:bg-white even:bg-stone-50/50" data-testid="email-template-row-{{ $template->id }}">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-stone-900">{{ $template->name }}</p>
                                        <p class="text-xs text-stone-500 font-mono mt-0.5">{{ $template->key }}</p>
                                        @if ($template->description)
                                            <p class="text-xs text-stone-500 mt-1">{{ $template->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-stone-600 hidden md:table-cell break-words max-w-md">
                                        <code>{{ $template->subject }}</code>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-stone-500 hidden lg:table-cell whitespace-nowrap">
                                        {{ $template->updated_at?->diffForHumans() ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                        <a href="{{ route('admin.email-templates.preview', $template) }}"
                                           target="_blank"
                                           class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-100 text-stone-800 hover:bg-stone-200 text-xs font-medium"
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
