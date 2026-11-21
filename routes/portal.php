<?php

use App\Portal\Controllers\ChoosePortalContactController;
use App\Portal\Controllers\ClosePortalTicketController;
use App\Portal\Controllers\CreatePortalTicketController;
use App\Portal\Controllers\DownloadPortalInvoicePdfController;
use App\Portal\Controllers\DownloadPortalReceiptPdfController;
use App\Portal\Controllers\DownloadPortalStatementController;
use App\Portal\Controllers\ListPortalArticlesController;
use App\Portal\Controllers\ListPortalAssetsController;
use App\Portal\Controllers\ListPortalInvoicesController;
use App\Portal\Controllers\ListPortalReceiptsController;
use App\Portal\Controllers\ListPortalServicesController;
use App\Portal\Controllers\ListPortalTicketsController;
use App\Portal\Controllers\LogOutOfPortalController;
use App\Portal\Controllers\ReopenPortalTicketController;
use App\Portal\Controllers\ShowPortalArticleController;
use App\Portal\Controllers\ShowPortalArticleImageController;
use App\Portal\Controllers\ShowPortalChoiceController;
use App\Portal\Controllers\ShowPortalDashboardController;
use App\Portal\Controllers\ShowPortalFileController;
use App\Portal\Controllers\ShowPortalStatementController;
use App\Portal\Controllers\ShowPortalTicketController;
use App\Portal\Controllers\StopViewingPortalAsContactController;
use App\Portal\Controllers\StorePortalTicketController;
use App\Portal\Controllers\StorePortalTicketFilesController;
use App\Portal\Controllers\StorePortalTicketReplyController;
use App\Portal\Middleware\AuthenticatePortalContact;
use Illuminate\Support\Facades\Route;

/*
 * The customer portal, where customers' contacts sign in to see their
 * company's tickets, billing, services, assets and articles.
 */
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('choose', ShowPortalChoiceController::class)->name('choose');
    Route::post('choose/{contact}', ChoosePortalContactController::class)->whereUuid('contact')->name('choose.store');

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

        Route::get('invoices', ListPortalInvoicesController::class)->name('invoices.index');
        Route::get('invoices/{invoice}/pdf', DownloadPortalInvoicePdfController::class)->name('invoices.pdf');
        Route::get('receipts', ListPortalReceiptsController::class)->name('receipts.index');
        Route::get('receipts/{payment}/pdf', DownloadPortalReceiptPdfController::class)->name('receipts.pdf');
        Route::get('statement', ShowPortalStatementController::class)->name('statement');
        Route::get('statement/pdf', DownloadPortalStatementController::class)->name('statement.pdf');

        Route::get('services', ListPortalServicesController::class)->name('services.index');
        Route::get('assets', ListPortalAssetsController::class)->name('assets.index');

        Route::get('articles', ListPortalArticlesController::class)->name('articles.index');
        Route::get('articles/{article}', ShowPortalArticleController::class)->name('articles.show');
        Route::get('articles/{article}/images/{image}', ShowPortalArticleImageController::class)->name('articles.images.show');
        Route::post('logout', LogOutOfPortalController::class)->name('logout');
        Route::post('view-as/stop', StopViewingPortalAsContactController::class)->name('view-as.stop');
    });
});
