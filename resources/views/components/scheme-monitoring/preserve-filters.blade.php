@props(['filters', 'except' => []])
{{-- Mirror every active dashboard filter as a hidden input so the form
     this is included in can submit without losing the OTHER form's state.
     Pass keys you DO control via `:except` to avoid round-tripping them.
     Arrays are emitted with `name[]` to round-trip multi-select state. --}}
@foreach ($filters as $key => $value)
    @continue (in_array($key, $except, true))
    @if (is_array($value))
        @foreach ($value as $v)
            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
        @endforeach
    @elseif ($value !== '' && $value !== null)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endif
@endforeach
