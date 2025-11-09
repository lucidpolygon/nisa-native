<x-layouts.app :title="__('Edit Integration')">
    <div class="card">
        <h1>Edit Integration</h1>

        {{-- Flash message --}}
        @if (session('success'))
        <div class="mb-4 p-3 rounded-md text-sm border border-green-200 bg-green-50 text-green-700">
            {{ session('success') }}
        </div>
        @endif

        <form action="{{ route('builder.integrations.update', $integration) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="border border-red-200 bg-red-50 text-red-700 text-sm rounded-md p-3">
                    <ul class="list-disc ml-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif  

        
            {{-- Title --}}
            <div>
                <label for="title">Title</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="{{ old('title', $integration->title) }}"
                    required
                >
            </div>

            {{-- Type (locked / readonly) --}}
            <div>
                <label for="type">Type</label>
                <input
                    type="text"
                    id="type_display"
                    value="{{ ucfirst($integration->type) }}"
                    readonly
                    class="cursor-not-allowed opacity-70"
                >
                {{-- Preserve the value for submission --}}
                <input type="hidden" name="type" value="{{ $integration->type }}">
            </div>

            {{-- Dynamic Fields --}}
            <div id="fields-container" class="space-y-4 mt-2">
                @php $config = $types[$integration->type] ?? null; @endphp
                @if ($config)
                <div data-type="{{ $integration->type }}">
                    <h2>{{ $config['label'] }} Configuration</h2>

                    @foreach($config['fields'] as $field)
                        @php $val = $integration->encrypted_value[$field['name']] ?? ''; @endphp
                        <div class="mt-2">
                            <label for="{{ $integration->type . '_' . $field['name'] }}">{{ $field['label'] }}</label>

                            @if($field['type'] === 'select')
                                <select
                                    id="{{ $integration->type . '_' . $field['name'] }}"
                                    name="encrypted_value[{{ $field['name'] }}]"
                                    data-required="{{ $field['required'] ? 'true' : 'false' }}"
                                    {{ $field['required'] ? 'required' : '' }}
                                >
                                    @foreach($field['options'] as $opt)
                                        <option value="{{ $opt }}" @selected($val === $opt)>{{ strtoupper($opt) }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input
                                    type="{{ $field['type'] }}"
                                    id="{{ $integration->type . '_' . $field['name'] }}"
                                    name="encrypted_value[{{ $field['name'] }}]"
                                    value="{{ $val }}"
                                    data-required="{{ $field['required'] ? 'true' : 'false' }}"
                                    {{ $field['required'] ? 'required' : '' }}
                                >
                            @endif
                        </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Active Toggle --}}
            <div>
                <label class="inline-flex items-center gap-2 text-sm text-muted">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" value="1" {{ $integration->active ? 'checked' : '' }}>
                    <span>Active</span>
                </label>
            </div>

            {{-- Buttons --}}
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Update Integration</button>
                <a href="{{ route('builder.integrations.index') }}" class="btn btn-secondary">Cancel</a>
                <button class="btn btn-secondary" onclick="document.getElementById('test-connection-form').submit();" type="button">Test Connection</button>
            </div>
        </form>
        <form action="{{ route('builder.integrations.test', $integration) }}" method="POST" class="hidden" id="test-connection-form">
            @csrf
        </form>        

    </div>
</x-layouts.app>
