<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\IntegrationRequest;

class IntegrationController extends Controller
{

    public function index()
    {
        $integrations = Integration::where('user_id', Auth::id())->paginate(10);
        return view('integrations.index', compact('integrations'));
    }

    public function create()
    {
        $types = config('integrations.types');
        return view('integrations.create', compact('types'));
    }

    public function store(IntegrationRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        $integration = Integration::create($data);

        return redirect()
        ->route('builder.integrations.edit', $integration,)
        ->with('success', 'Integration saved.');

    }

    public function show(Integration $integration)
    {
    }

    public function edit(Integration $integration)
    {
        $this->authorizeAccess($integration);
    
        // Load integration type definitions
        $types = config('integrations.types');
    
        return view('integrations.edit', compact('integration', 'types'));
    }
    

    public function update(IntegrationRequest $request, Integration $integration)
    {
        $this->authorizeAccess($integration);

        // Only allow active + encrypted_value to be updated
        $data = $request->only(['active', 'encrypted_value','title']);

        // Normalize checkbox value
        $data['active'] = $request->has('active');

        if ($request->has('encrypted_value')) {
            $data['encrypted_value'] = array_merge(
                $integration->encrypted_value ?? [],
                $request->input('encrypted_value', [])
            );
        }

        $integration->update($data);
        return redirect()->route('builder.integrations.index')->with('success', 'Integration updated successfully.');
    }

    public function destroy(Integration $integration)
    {
        $this->authorizeAccess($integration);
        $integration->delete();
        return redirect()
            ->route('builder.integrations.index')
            ->with('success', 'Integration deleted.');
    }

    protected function authorizeAccess(Integration $integration)
    {
        abort_unless($integration->user_id === Auth::id(), 403);
    }

    public function testConnection(Integration $integration)
    {
        $this->authorizeAccess($integration);

        try {
            $service = app(\App\Services\IntegrationTester::class);
            $result = $service->test($integration);

            $integration->update(['last_verified_at' => now()]);    

            return back()->with('success', "Connection successful: {$result}");
        } catch (\Throwable $e) {
            return back()->withErrors(['connection' => 'Test failed: ' . $e->getMessage()]);
        }
    }

}
