@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null]) {{-- Values this component receives; type, value and hint are optional --}}

<div class="mb-3"> {{-- One field with spacing below --}}
    <label for="{{ $name }}" class="form-label">{{ $label }}</label> {{-- The field's label --}}
    <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}> {{-- The box; keeps the typed value after an error (never a password); extra attributes like required are passed through --}}
    @if ($hint) {{-- A small help text was given --}}
        <div class="form-text">{{ $hint }}</div> {{-- Show it under the box --}}
    @endif {{-- End of the help text --}}
    @error($name) {{-- There is an error for this field --}}
        <div class="invalid-feedback">{{ $message }}</div> {{-- Show the error text in red --}}
    @enderror {{-- End of the error --}}
</div> {{-- End of the field --}}
