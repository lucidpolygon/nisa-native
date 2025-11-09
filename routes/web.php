<?php

use Laravel\Fortify\Features;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\Settings\Appearance;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IntegrationController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('builder', 'builder')
    ->middleware(['auth', 'verified'])
    ->name('builder');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

Route::middleware(['auth'])->prefix('builder')->as('builder.')->group(function () {
    Route::resource('integrations', IntegrationController::class);
    
    // Optional: test connection route
    Route::post('integrations/{integration}/test', [IntegrationController::class, 'testConnection'])
        ->name('integrations.test');
});
