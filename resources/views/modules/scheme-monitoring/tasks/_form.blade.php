@csrf
<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-stone-700">Scheme</label>
        <select name="scheme_id" required class="mt-1 block w-full rounded-lg border-stone-300" data-testid="task-scheme">
            @foreach ($schemes as $s)
                <option value="{{ $s->id }}" @selected(old('scheme_id', $task->scheme_id) === $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
        @error('scheme_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700">Title</label>
        <input name="title" type="text" required maxlength="255" value="{{ old('title', $task->title) }}" class="mt-1 block w-full rounded-lg border-stone-300" data-testid="task-title">
        @error('title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700">Description</label>
        <textarea name="description" rows="4" class="mt-1 block w-full rounded-lg border-stone-300">{{ old('description', $task->description) }}</textarea>
    </div>
    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-stone-700">Deadline</label>
            <input name="deadline" type="date" required value="{{ old('deadline', optional($task->deadline)->format('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border-stone-300">
            @error('deadline') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-stone-700">Status</label>
            <select name="status" class="mt-1 block w-full rounded-lg border-stone-300">
                @foreach (\App\Modules\SchemeMonitoring\Models\Task::STATUSES as $v => $label)
                    <option value="{{ $v }}" @selected(old('status', $task->status ?? 'pending') === $v)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-stone-700">Priority</label>
            <select name="priority" class="mt-1 block w-full rounded-lg border-stone-300">
                @foreach (\App\Modules\SchemeMonitoring\Models\Task::PRIORITIES as $v => $label)
                    <option value="{{ $v }}" @selected(old('priority', $task->priority ?? 'normal') === $v)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700">Assign to</label>
        <select name="assigned_to" required class="mt-1 block w-full rounded-lg border-stone-300" data-testid="task-assignee">
            @foreach (\App\Models\User::whereIn('id', $assignableUserIds)->orderBy('name')->get() as $u)
                <option value="{{ $u->id }}" @selected(old('assigned_to', $task->assigned_to) === $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        @error('assigned_to') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <button type="submit" class="inline-flex items-center px-5 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm font-medium">Save task</button>
</div>
