<x-layouts.app :title="__('Add Integration')">
    <div class="max-w-2xl mx-auto space-y-6">
        <div class="card">
            <h1>Add Integration</h1>

            <form action="{{ route('builder.integrations.store') }}" method="POST" class="space-y-4">
                @csrf

                {{-- Title --}}
                <div>
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" required>
                </div>

                {{-- Type --}}
                <div>
                    <label for="type">Type</label>
                    <select id="type" name="type" required>
                        <option value="">Select type</option>
                        @foreach($types as $key => $config)
                            <option value="{{ $key }}">{{ $config['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Dynamic Fields --}}
                <div id="fields-container" class="space-y-4 mt-2">
                    @foreach($types as $key => $config)
                        <div class="type-fields hidden" data-type="{{ $key }}">
                            <h2>{{ $config['label'] }} Configuration</h2>

                            @foreach($config['fields'] as $field)
                                <div class="mt-2">
                                    <label for="{{ $key . '_' . $field['name'] }}">{{ $field['label'] }}</label>

                                    @if($field['type'] === 'select')
                                    <select id="{{ $key . '_' . $field['name'] }}"
                                        name="encrypted_value[{{ $field['name'] }}]"
                                        data-required="{{ $field['required'] ? 'true' : 'false' }}"
                                        {{ $field['required'] ? 'required' : '' }}>
                                        @foreach($field['options'] as $opt)
                                            <option value="{{ $opt }}">{{ strtoupper($opt) }}</option>
                                        @endforeach
                                    </select>
                                    @else
                                    <input type="{{ $field['type'] }}"
                                    id="{{ $key . '_' . $field['name'] }}"
                                    name="encrypted_value[{{ $field['name'] }}]"
                                    data-required="{{ $field['required'] ? 'true' : 'false' }}"
                                    {{ $field['required'] ? 'required' : '' }}>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                {{-- Active Toggle --}}
                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-[var(--text-muted)]">
                        <input type="checkbox" name="active" value="1" checked>
                        <span>Active</span>
                    </label>
                </div>

                {{-- Buttons --}}
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn btn-primary">Save Integration</button>
                    <a href="{{ route('builder.integrations.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const typeSelect = document.getElementById('type');
        const groups = document.querySelectorAll('.type-fields');
        
        function updateFieldVisibility(selected) {
            groups.forEach(group => {
                const isVisible = group.dataset.type === selected;
        
                // Toggle visibility
                group.classList.toggle('hidden', !isVisible);
        
                // Disable required attributes for hidden inputs
                group.querySelectorAll('input, select, textarea').forEach(input => {
                    if (!isVisible) {
                        input.removeAttribute('required');
                    } else if (input.dataset.required === 'true') {
                        input.setAttribute('required', 'required');
                    }
                });
            });
        }
        
        typeSelect.addEventListener('change', e => {
            updateFieldVisibility(e.target.value);
        });
        
        // Initialize on load
        updateFieldVisibility(typeSelect.value);
    </script>
        
</x-layouts.app>
