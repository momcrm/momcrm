<?php

use App\Team\Controllers\SwitchCurrentTeamController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::put('current-team', SwitchCurrentTeamController::class)->name('current-team.update');
});

require __DIR__.'/settings.php';
