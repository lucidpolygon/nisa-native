<?php

use App\Models\User;
use App\Models\Integration;
use App\Services\IntegrationTester;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/* --------------------------------------------
|  GUEST ACCESS
|---------------------------------------------*/
test('guest is redirected to login for all integration routes', function () {
    $routes = [
        'builder.integrations.index',
        'builder.integrations.create',
        'builder.integrations.store',
        'builder.integrations.edit',
        'builder.integrations.update',
        'builder.integrations.destroy',
    ];

    $integration = Integration::factory()->create();

    foreach ($routes as $route) {
        $response = $this->get(route($route, $integration));
        $response->assertRedirect('/login');
    }
});

/* --------------------------------------------
|  INDEX
|---------------------------------------------*/
test('index shows only current user integrations', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Integration::factory()->for($user)->create(['title' => 'User Integration']);
    Integration::factory()->for($other)->create(['title' => 'Other Integration']);

    $response = $this->actingAs($user)->get(route('builder.integrations.index'));

    $response->assertSee('User Integration');
    $response->assertDontSee('Other Integration');
});

/* --------------------------------------------
|  STORE (CREATE)
|---------------------------------------------*/
test('authenticated user can create integration with encrypted data', function () {
    $user = User::factory()->create();

    $payload = [
        'title' => 'My Airtable',
        'type' => 'airtable',
        'active' => true,
        'encrypted_value' => ['pat' => 'secret_token'],
    ];

    $response = $this->actingAs($user)
        ->post(route('builder.integrations.store'), $payload);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $integration = Integration::first();

    expect($integration)
        ->title->toBe('My Airtable')
        ->user_id->toBe($user->id)
        ->active->toBeTrue();

    // Verify encryption
    $raw = $integration->getRawOriginal('encrypted_value');
    expect($raw)->not->toContain('secret_token');

    $decrypted = $integration->encrypted_value;
    expect($decrypted['pat'])->toBe('secret_token');
});

test('store validates required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('builder.integrations.store'), []);
    $response->assertSessionHasErrors(['title', 'type', 'encrypted_value']);
});

test('store rejects invalid type', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('builder.integrations.store'), [
        'title' => 'Bad Type',
        'type' => 'invalid_type',
        'encrypted_value' => ['a' => 'b'],
    ]);

    $response->assertSessionHasErrors('type');
});

/* --------------------------------------------
|  SLUG GENERATION
|---------------------------------------------*/
test('slug is generated and unique per user', function () {
    $user = User::factory()->create();

    Integration::factory()->for($user)->create(['title' => 'My DB']);
    Integration::factory()->for($user)->create(['title' => 'My DB']);

    $slugs = Integration::pluck('slug')->toArray();
    expect($slugs[0])->toBe('my-db')
        ->and($slugs[1])->toBe('my-db-1');
});

/* --------------------------------------------
|  EDIT / UPDATE
|---------------------------------------------*/
test('owner can update only title, active, and encrypted_value', function () {
    $user = User::factory()->create();

    $integration = Integration::factory()->for($user)->create([
        'title' => 'Old Title',
        'type' => 'mysql',
        'encrypted_value' => ['host' => 'localhost'],
        'active' => false,
    ]);

    $this->actingAs($user)->put(route('builder.integrations.update', $integration), [
        'title' => 'New Title',
        'active' => true,
        'type' => $integration->type,
        'encrypted_value' => ['host' => '127.0.0.1'],
        'slug' => 'tamper-attempt', // ignored
    ]);

    $integration->refresh();

    expect($integration)
        ->title->toBe('New Title')
        ->active->toBeTrue()
        ->and($integration->slug)->not->toBe('tamper-attempt')
        ->and($integration->encrypted_value['host'])->toBe('127.0.0.1');
});

test('non-owner cannot update integration', function () {
    [$user, $other] = User::factory()->count(2)->create();

    $integration = Integration::factory()->for($other)->create();

    $this->actingAs($user)
        ->put(route('builder.integrations.update', $integration))
        ->assertNotFound();
});

/* --------------------------------------------
|  DESTROY (SOFT DELETE)
|---------------------------------------------*/
test('owner can soft delete integration', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->for($user)->create();

    $this->actingAs($user)->delete(route('builder.integrations.destroy', $integration))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Integration::withTrashed()->count())->toBe(1)
        ->and(Integration::count())->toBe(0);
});

test('non-owner cannot delete integration', function () {
    [$user, $other] = User::factory()->count(2)->create();
    $integration = Integration::factory()->for($other)->create();

    $this->actingAs($user)
        ->delete(route('builder.integrations.destroy', $integration))
        ->assertNotFound();

    expect(Integration::count())->toBe(1);
});

/* --------------------------------------------
|  TEST CONNECTION
|---------------------------------------------*/
test('test connection updates last_verified_at on success', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->for($user)->create();

    $mock = Mockery::mock(IntegrationTester::class);
    $mock->shouldReceive('test')->once()->andReturn('ok');
    app()->instance(IntegrationTester::class, $mock);

    $this->actingAs($user)
        ->post(route('builder.integrations.test', $integration))
        ->assertRedirect()
        ->assertSessionHas('success');

    $integration->refresh();
    expect($integration->last_verified_at)->not->toBeNull();
});

test('test connection failure returns error message', function () {
    $user = User::factory()->create();
    $integration = Integration::factory()->for($user)->create();

    $mock = Mockery::mock(IntegrationTester::class);
    $mock->shouldReceive('test')->andThrow(new Exception('Failed'));
    app()->instance(IntegrationTester::class, $mock);

    $this->actingAs($user)
        ->post(route('builder.integrations.test', $integration))
        ->assertSessionHasErrors(['connection']);
});

test('non-owner cannot test connection', function () {
    [$user, $other] = User::factory()->count(2)->create();
    $integration = Integration::factory()->for($other)->create();

    $this->actingAs($user)
        ->post(route('builder.integrations.test', $integration))
        ->assertNotFound();
});

/* --------------------------------------------
|  SOFT DELETE VISIBILITY
|---------------------------------------------*/
test('soft-deleted integrations are hidden from index', function () {
    $user = User::factory()->create();
    $active = Integration::factory()->for($user)->create(['title' => 'Visible']);
    $deleted = Integration::factory()->for($user)->create(['title' => 'Deleted']);
    $deleted->delete();

    $response = $this->actingAs($user)->get(route('builder.integrations.index'));

    $response->assertSee('Visible');
    $response->assertDontSee('Deleted');
});

/* --------------------------------------------
|  PAGINATION
|---------------------------------------------*/
test('index paginates results', function () {
    $user = User::factory()->create();
    Integration::factory()->for($user)->count(12)->create();

    $response = $this->actingAs($user)->get(route('builder.integrations.index'));

    $response->assertSee('Next'); // basic pagination link check
    expect(Integration::count())->toBe(12);
});

test('update merges new encrypted_value keys without overwriting existing ones', function () {
    $user = User::factory()->create();

    $integration = Integration::factory()->for($user)->create([
        'type' => 'mysql',
        'encrypted_value' => ['host' => 'localhost', 'username' => 'root', 'password' => 'secret'],
    ]);

    // Simulate updating only one field
    $payload = [
        'title' => 'Updated DB',
        'active' => true,
        'type' => 'mysql',
        'encrypted_value' => ['host' => '127.0.0.1'],
    ];

    $this->actingAs($user)->put(route('builder.integrations.update', $integration), $payload);

    $integration->refresh();

    expect($integration->encrypted_value)->toMatchArray([
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => 'secret',
    ]);
});

test('create view renders dynamic fields for each integration type', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('builder.integrations.create'));

    $response->assertOk();
    $response->assertSee('Personal Access Token');
    $response->assertSee('API Key');
    $response->assertSee('Host');
});

test('edit view pre-fills fields from encrypted_value config', function () {
    $user = User::factory()->create();

    $integration = Integration::factory()->for($user)->create([
        'type' => 'mysql',
        'encrypted_value' => ['host' => 'localhost', 'username' => 'root'],
    ]);

    $response = $this->actingAs($user)->get(route('builder.integrations.edit', $integration));

    $response->assertOk();
    $response->assertSee('localhost');
    $response->assertSee('Username');
});
