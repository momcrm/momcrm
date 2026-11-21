<?php

use App\Auth\Controllers\ChooseWorkspaceController;
use App\Auth\Controllers\ConfirmWithCodeController;
use App\Auth\Controllers\CreateAccountController;
use App\Auth\Controllers\HandleProviderCallbackController;
use App\Auth\Controllers\RedirectToProviderController;
use App\Auth\Controllers\SendConfirmationCodeController;
use App\Auth\Controllers\SendEmailCodeController;
use App\Auth\Controllers\ShowContinueController;
use App\Auth\Controllers\ShowCreateAccountController;
use App\Auth\Controllers\ShowEmailCodeController;
use App\Auth\Controllers\VerifyEmailCodeController;
use Illuminate\Support\Facades\Route;

// Notion-style sign-in: an email, then a code. Log-in (/login) is Fortify's route rendering the same page.
Route::middleware('guest')->group(function () {
    Route::get('signup', ShowContinueController::class)->name('signup');
    Route::permanentRedirect('register', '/signup');

    Route::post('auth/code', SendEmailCodeController::class)->middleware('throttle:email-code-send')->name('auth.code.send');
    Route::get('auth/code', ShowEmailCodeController::class)->name('auth.code');
    Route::post('auth/code/verify', VerifyEmailCodeController::class)->middleware('throttle:email-code-verify')->name('auth.code.verify');

    Route::get('signup/profile', ShowCreateAccountController::class)->name('signup.profile');
    Route::post('signup/profile', CreateAccountController::class)->name('signup.profile.store');

    Route::get('auth/{provider}/redirect', RedirectToProviderController::class)->name('auth.social.redirect');
});

// Outside the guest group, so a customer's contact signed in to the portal can
// sign in again, as staff or as one of their other contacts.
Route::get('auth/{provider}/callback', HandleProviderCallbackController::class)->name('auth.social.callback');

Route::post('choose/workspace', ChooseWorkspaceController::class)->name('choose.workspace');

Route::middleware('auth')->group(function () {
    Route::post('user/confirm-with-code/send', SendConfirmationCodeController::class)
        ->middleware('throttle:email-code-send')
        ->name('password.confirm.code.send');
    Route::post('user/confirm-with-code', ConfirmWithCodeController::class)
        ->middleware('throttle:email-code-verify')
        ->name('password.confirm.code');
});
