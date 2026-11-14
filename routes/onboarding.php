<?php

use App\Onboarding\Controllers\CompleteOnboardingController;
use App\Onboarding\Controllers\SendInvitesController;
use App\Onboarding\Controllers\ShowAboutController;
use App\Onboarding\Controllers\ShowInviteController;
use App\Onboarding\Controllers\ShowStartController;
use App\Onboarding\Controllers\ShowWorkspaceController;
use App\Onboarding\Controllers\StartWithImportController;
use App\Onboarding\Controllers\StartWithSampleDataController;
use App\Onboarding\Controllers\UpdateAboutController;
use App\Onboarding\Controllers\UpdateWorkspaceController;
use App\Onboarding\Middleware\RedirectIfOnboarded;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', RedirectIfOnboarded::class])
    ->prefix('onboarding')
    ->name('onboarding.')
    ->group(function () {
        Route::get('about', ShowAboutController::class)->name('about');
        Route::put('about', UpdateAboutController::class)->name('about.update');

        Route::get('workspace', ShowWorkspaceController::class)->name('workspace');
        Route::put('workspace', UpdateWorkspaceController::class)->name('workspace.update');

        Route::get('invite', ShowInviteController::class)->name('invite');
        Route::post('invite', SendInvitesController::class)->name('invite.store');

        Route::get('start', ShowStartController::class)->name('start');
        Route::post('complete', CompleteOnboardingController::class)->name('complete');
        Route::post('start/sample', StartWithSampleDataController::class)->name('start.sample');
        Route::post('start/import', StartWithImportController::class)->name('start.import');
    });
