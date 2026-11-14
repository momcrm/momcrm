<?php

use App\Dashboard\Controllers\ShowDashboardController;
use App\Onboarding\Controllers\DismissGettingStartedController;
use App\Onboarding\Middleware\EnsureOnboarded;
use App\Search\Controllers\SearchWorkspaceController;
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
    Route::get('dashboard', ShowDashboardController::class)->middleware(EnsureOnboarded::class)->name('dashboard');
    Route::post('getting-started/dismiss', DismissGettingStartedController::class)->name('getting-started.dismiss');

    Route::put('current-team', SwitchCurrentTeamController::class)->name('current-team.update');

    Route::inertia('design-system', 'design-system')->name('design-system');
    Route::get('search', SearchWorkspaceController::class)->name('search');
});

require __DIR__.'/auth.php';
require __DIR__.'/crm.php';
require __DIR__.'/onboarding.php';
require __DIR__.'/settings.php';
