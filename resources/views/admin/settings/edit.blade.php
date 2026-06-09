@extends('whatsapp::layouts.admin')

@section('title', 'Settings')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">WhatsApp Settings</h1>
        <p class="text-muted mb-0">All runtime settings are stored in the database and applied immediately — no <code>.env</code> changes required.</p>
    </div>

    <form action="{{ route('whatsapp.admin.settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        @foreach ($groups as $groupKey => $groupLabel)
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">{{ $groupLabel }}</div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach ($definitions as $key => $definition)
                            @continue($definition['group'] !== $groupKey)
                            @php
                                $field = str_replace('.', '_', $key);
                                $value = old($field, $values[$key] ?? $definition['default']);
                                $isBoolean = $definition['type'] === 'boolean';
                            @endphp
                            <div class="col-md-6">
                                <label class="form-label" for="{{ $field }}">{{ $definition['label'] }}</label>

                                @if ($isBoolean)
                                    @php $enabled = filter_var($value, FILTER_VALIDATE_BOOLEAN); @endphp
                                    <select name="{{ $field }}" id="{{ $field }}" class="form-select @error($field) is-invalid @enderror">
                                        <option value="1" @selected($enabled)>Enabled</option>
                                        <option value="0" @selected(! $enabled)>Disabled</option>
                                    </select>
                                @elseif (isset($definition['options']))
                                    <select name="{{ $field }}" id="{{ $field }}" class="form-select @error($field) is-invalid @enderror">
                                        @foreach ($definition['options'] as $option)
                                            <option value="{{ $option }}" @selected((string) $value === $option)>{{ ucfirst($option) }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input
                                        type="{{ $definition['type'] === 'float' ? 'number' : ($definition['type'] === 'integer' ? 'number' : 'text') }}"
                                        name="{{ $field }}"
                                        id="{{ $field }}"
                                        class="form-control @error($field) is-invalid @enderror"
                                        value="{{ $value }}"
                                        @if (isset($definition['min'])) min="{{ $definition['min'] }}" @endif
                                        @if (isset($definition['max'])) max="{{ $definition['max'] }}" @endif
                                        @if (isset($definition['step'])) step="{{ $definition['step'] }}" @endif
                                        @if (! ($definition['nullable'] ?? false)) required @endif
                                    >
                                @endif

                                @if (! empty($definition['help']))
                                    <div class="form-text">{{ $definition['help'] }}</div>
                                @endif
                                <div class="form-text">Key: <code>{{ $key }}</code></div>
                                @error($field)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">Save Settings</button>
            <a href="{{ route('whatsapp.admin.system') }}" class="btn btn-outline-secondary">System Monitor</a>
        </div>
    </form>
@endsection
