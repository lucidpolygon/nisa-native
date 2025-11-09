<x-layouts.app :title="__('Integrations')">
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1>Integrations</h1>
            <a href="{{ route('builder.integrations.create') }}" class="btn btn-primary">
                + Add Integration
            </a>
        </div>

        {{-- Flash message --}}
        @if (session('success'))
            <div class="mb-4 p-3 rounded-md text-sm border border-green-200 bg-green-50 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Integrations Table --}}
        @if($integrations->count())
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Last Verified</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($integrations as $integration)
                            <tr>
                                <td>{{ $integration->title }}</td>
                                <td class="capitalize text-muted">{{ $integration->type }}</td>
                                <td>
                                    @if($integration->active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-muted">
                                    {{ $integration->last_verified_at ? $integration->last_verified_at->format('Y-m-d H:i') : '—' }}
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('builder.integrations.edit', $integration) }}" class="">
                                            Edit
                                        </a>
                                        <form action="{{ route('builder.integrations.destroy', $integration) }}" method="POST" onsubmit="return confirm('Delete this integration?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-link btn-link-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $integrations->links() }}
            </div>
        @else
            <div class="text-center py-10">
                <p class="text-muted">No integrations added yet.</p>
                <a href="{{ route('builder.integrations.create') }}" class="btn btn-primary mt-4">
                    Add your first integration
                </a>
            </div>
        @endif
    </div>
</x-layouts.app>
