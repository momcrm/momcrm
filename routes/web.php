<?php

use App\Onboarding\Middleware\EnsureOnboarded;
use App\Team\Controllers\SwitchCurrentTeamController;
use App\TeamInvitation\Controllers\AcceptTeamInvitationController;
use App\TeamInvitation\Controllers\ShowTeamInvitationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('invitations/{token}', ShowTeamInvitationController::class)->name('team-invitations.show');
Route::post('invitations/{token}/accept', AcceptTeamInvitationController::class)
    ->middleware('auth')
    ->name('team-invitations.accept');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->middleware(EnsureOnboarded::class)->name('dashboard');

    Route::put('current-team', SwitchCurrentTeamController::class)->name('current-team.update');
});

require __DIR__.'/onboarding.php';
require __DIR__.'/settings.php';
