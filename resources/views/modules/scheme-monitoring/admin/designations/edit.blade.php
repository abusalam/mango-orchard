<x-admin-layout :title="'Edit '.$designation->name" active="monitoring-designations">
    <h1 class="text-3xl font-semibold tracking-tight mb-6">Edit designation</h1>
    <form method="POST" action="{{ route('admin.monitoring.designations.update', $designation) }}" class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-6 max-w-2xl">
        @method('PUT')
        @include('scheme-monitoring::admin.designations._form')
    </form>
</x-admin-layout>
