<x-admin-layout title="New designation" active="monitoring-designations">
    <h1 class="text-3xl font-semibold tracking-tight mb-6">New designation</h1>
    <form method="POST" action="{{ route('admin.monitoring.designations.store') }}" class="bg-white rounded-2xl border border-stone-200 p-6 max-w-2xl">
        @include('scheme-monitoring::admin.designations._form')
    </form>
</x-admin-layout>
