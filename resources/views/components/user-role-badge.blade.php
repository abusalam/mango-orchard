@props(['user'])

@php
    $priorityOrder = [\App\Roles::SUPERUSER, \App\Roles::CURATOR, \App\Roles::VIEWER];
    $primaryRole = $user->roles
        ->sortBy(function ($role) use ($priorityOrder) {
            $index = array_search($role->name, $priorityOrder, true);
            return $index === false ? PHP_INT_MAX : $index;
        })
        ->first();
@endphp

@if ($primaryRole)
    <span @class([
        'inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium border tracking-wide',
        'bg-amber-100 text-amber-900 border-amber-200' => $primaryRole->name === \App\Roles::SUPERUSER,
        'bg-stone-100 text-stone-700 border-stone-200' => $primaryRole->name !== \App\Roles::SUPERUSER,
    ]) data-testid="user-role-badge">{{ $primaryRole->name }}</span>
@endif
