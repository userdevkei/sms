@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null, 'options' => [], 'placeholder' => null, 'col' => 'col-12', 'rows' => 3])
@php
    // "guardians[0][phone]" → "guardians.0.phone" so old() and error bags find it
    $key     = str_replace(['[', ']'], ['.', ''], $name);
    $current = old($key, $value);
    $id      = 'f_'.preg_replace('/[^A-Za-z0-9_]/', '_', $name);
    $invalid = $errors->has($key) ? ' is-invalid' : '';
@endphp
<div class="{{ $col }}">
    <label for="{{ $id }}" class="form-label">{{ $label }}@if ($required)<span class="text-danger"> *</span>@endif</label>

    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}" class="form-control{{ $invalid }}" {{ $attributes }}>{{ $current }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" class="form-select{{ $invalid }}" {{ $attributes }}>
            <option value="">Select…</option>
            @foreach ($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected((string) $current === (string) $optValue)>{{ $optLabel }}</option>
            @endforeach
        </select>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ $current }}" placeholder="{{ $placeholder }}" class="form-control{{ $invalid }}" {{ $attributes }}>
    @endif

    @if ($hint)
        <div class="form-text">{{ $hint }}</div>
    @endif
    @error($key)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
