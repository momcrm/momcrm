<?php

use App\Activity\Controllers\ListAccountActivityController;
use App\Activity\Controllers\ListTeamActivityController;
use App\Team\Controllers\EditTeamController;
use App\Team\Controllers\UpdateTeamController;
use App\TeamInvitation\Controllers\DeleteTeamInvitationController;
use App\TeamInvitation\Controllers\StoreTeamInvitationController;
use App\User\Controllers\DeleteProfileController;
use App\User\Controllers\EditProfileController;
use App\User\Controllers\EditSecurityController;
use App\User\Controllers\UpdatePasswordController;
use App\User\Controllers\UpdateProfileController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', EditProfileController::class)->name('profile.edit');
    Route::patch('settings/profile', UpdateProfileController::class)->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', DeleteProfileController::class)->name('profile.destroy');

    Route::get('settings/security', EditSecurityController::class)
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', UpdatePasswordController::class)
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/team', EditTeamController::class)->name('team.edit');
    Route::patch('settings/team', UpdateTeamController::class)->name('team.update');
    Route::post('settings/team/invitations', StoreTeamInvitationController::class)->name('team.invitations.store');
    Route::delete('settings/team/invitations/{invitation}', DeleteTeamInvitationController::class)->name('team.invitations.destroy');
    Route::get('settings/team/activity', ListTeamActivityController::class)->name('team-activity.index');

    Route::get('settings/activity', ListAccountActivityController::class)->name('activity.index');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
