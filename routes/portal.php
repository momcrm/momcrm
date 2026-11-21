<?php

use App\Portal\Controllers\ChoosePortalContactController;
use App\Portal\Controllers\ClosePortalTicketController;
use App\Portal\Controllers\CreatePortalTicketController;
use App\Portal\Controllers\DownloadPortalReceiptPdfController;
use App\Portal\Controllers\DownloadPortalStatementController;
use App\Portal\Controllers\ListPortalAssetsController;
use App\Portal\Controllers\ListPortalReceiptsController;
use App\Portal\Controllers\ReopenPortalTicketController;
use App\Portal\Controllers\ShowPortalChoiceController;
use App\Portal\Controllers\ShowPortalFileController;
use App\Portal\Controllers\ShowPortalStatementController;
use App\Portal\Controllers\StopViewingPortalAsContactController;
use App\Portal\Controllers\StorePortalTicketFilesController;
use App\Portal\Controllers\StorePortalTicketReplyController;
use App\Portal\Middleware\AuthenticatePortalContact;
use Illuminate\Support\Facades\Route;

/*
 * The customer portal's own pages, where customers' contacts see their
 * company's tickets, billing, services, assets and articles. Its pages at
 * addresses staff use too, such as /tickets, are with the app's (StaffOrContact).
 */
Route::name('portal.')->group(function () {
    Route::get('choose', ShowPortalChoiceController::class)->name('choose');
    Route::post('choose/{contact}', ChoosePortalContactController::class)->whereUuid('contact')->name('choose.store');

    Route::middleware(AuthenticatePortalContact::class)->group(function () {
        Route::get('tickets/create', CreatePortalTicketController::class)->name('tickets.create');
        Route::post('tickets/{ticket}/replies', StorePortalTicketReplyController::class)->whereUuid('ticket')->name('tickets.replies.store');
        Route::post('tickets/{ticket}/files', StorePortalTicketFilesController::class)->whereUuid('ticket')->name('tickets.files.store');
        Route::post('tickets/{ticket}/close', ClosePortalTicketController::class)->whereUuid('ticket')->name('tickets.close');
        Route::post('tickets/{ticket}/reopen', ReopenPortalTicketController::class)->whereUuid('ticket')->name('tickets.reopen');
        Route::get('files/{attachment}', ShowPortalFileController::class)->whereUuid('attachment')->name('files.show');

        Route::get('receipts', ListPortalReceiptsController::class)->name('receipts.index');
        Route::get('receipts/{payment}/pdf', DownloadPortalReceiptPdfController::class)->whereUuid('payment')->name('receipts.pdf');
        Route::get('statement', ShowPortalStatementController::class)->name('statement');
        Route::get('statement/pdf', DownloadPortalStatementController::class)->name('statement.pdf');

        Route::get('assets', ListPortalAssetsController::class)->name('assets.index');

        Route::post('view-as/stop', StopViewingPortalAsContactController::class)->name('view-as.stop');
    });
});
