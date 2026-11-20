<?php

use App\Portal\Controllers\ChoosePortalContactController;
use App\Portal\Controllers\ClosePortalTicketController;
use App\Portal\Controllers\CreatePortalTicketController;
use App\Portal\Controllers\ListPortalTicketsController;
use App\Portal\Controllers\LogOutOfPortalController;
use App\Portal\Controllers\RedirectPortalToProviderController;
use App\Portal\Controllers\ReopenPortalTicketController;
use App\Portal\Controllers\SendPortalCodeController;
use App\Portal\Controllers\ShowPortalChoiceController;
use App\Portal\Controllers\ShowPortalCodeController;
use App\Portal\Controllers\ShowPortalDashboardController;
use App\Portal\Controllers\ShowPortalFileController;
use App\Portal\Controllers\ShowPortalLoginController;
use App\Portal\Controllers\ShowPortalTicketController;
use App\Portal\Controllers\StorePortalTicketController;
use App\Portal\Controllers\StorePortalTicketFilesController;
use App\Portal\Controllers\StorePortalTicketReplyController;
use App\Portal\Controllers\VerifyPortalCodeController;
use App\Portal\Middleware\AuthenticatePortalContact;
use App\Portal\Middleware\RedirectIfInPortal;
use Illuminate\Support\Facades\Route;

/*
 * The customer portal, where customers' contacts sign in to see their
 * company's tickets, billing, services, assets and articles.
 */
Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware(RedirectIfInPortal::class)->group(function () {
        Route::get('login', ShowPortalLoginController::class)->name('login');
        Route::post('login/code', SendPortalCodeController::class)->middleware('throttle:email-code-send')->name('login.code.send');
        Route::get('login/code', ShowPortalCodeController::class)->name('login.code');
        Route::post('login/code/verify', VerifyPortalCodeController::class)->middleware('throttle:email-code-verify')->name('login.code.verify');
        Route::get('auth/{provider}/redirect', RedirectPortalToProviderController::class)->name('social.redirect');
    });

    Route::get('choose', ShowPortalChoiceController::class)->name('choose');
    Route::post('choose/{contact}', ChoosePortalContactController::class)->name('choose.store');

    Route::middleware(AuthenticatePortalContact::class)->group(function () {
        Route::get('/', ShowPortalDashboardController::class)->name('home');

        Route::get('tickets', ListPortalTicketsController::class)->name('tickets.index');
        Route::get('tickets/create', CreatePortalTicketController::class)->name('tickets.create');
        Route::post('tickets', StorePortalTicketController::class)->name('tickets.store');
        Route::get('tickets/{ticket}', ShowPortalTicketController::class)->name('tickets.show');
        Route::post('tickets/{ticket}/replies', StorePortalTicketReplyController::class)->name('tickets.replies.store');
        Route::post('tickets/{ticket}/files', StorePortalTicketFilesController::class)->name('tickets.files.store');
        Route::post('tickets/{ticket}/close', ClosePortalTicketController::class)->name('tickets.close');
        Route::post('tickets/{ticket}/reopen', ReopenPortalTicketController::class)->name('tickets.reopen');
        Route::get('files/{attachment}', ShowPortalFileController::class)->name('files.show');
        Route::post('logout', LogOutOfPortalController::class)->name('logout');
    });
});
