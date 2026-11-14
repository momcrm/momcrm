<?php

use App\Asset\Controllers\CreateAssetController;
use App\Asset\Controllers\DeleteAssetController;
use App\Asset\Controllers\EditAssetController;
use App\Asset\Controllers\StoreAssetController;
use App\Asset\Controllers\UpdateAssetController;
use App\Contact\Controllers\CreateContactController;
use App\Contact\Controllers\DeleteContactController;
use App\Contact\Controllers\EditContactController;
use App\Contact\Controllers\ListContactsController;
use App\Contact\Controllers\StoreContactController;
use App\Contact\Controllers\UpdateContactController;
use App\Customer\Controllers\CreateCustomerController;
use App\Customer\Controllers\DeleteCustomerController;
use App\Customer\Controllers\EditCustomerController;
use App\Customer\Controllers\ImportCustomersController;
use App\Customer\Controllers\ListCustomersController;
use App\Customer\Controllers\ShowCustomerController;
use App\Customer\Controllers\ShowImportCustomersController;
use App\Customer\Controllers\StoreCustomerController;
use App\Customer\Controllers\UpdateCustomerController;
use App\Favorite\Controllers\ToggleFavoriteController;
use App\Invoice\Controllers\CreateInvoiceController;
use App\Invoice\Controllers\EditInvoiceController;
use App\Invoice\Controllers\ListInvoicesController;
use App\Invoice\Controllers\MarkInvoicePaidController;
use App\Invoice\Controllers\MarkInvoiceSentController;
use App\Invoice\Controllers\ShowInvoiceController;
use App\Invoice\Controllers\StoreInvoiceController;
use App\Invoice\Controllers\UpdateInvoiceController;
use App\Invoice\Controllers\VoidInvoiceController;
use App\Location\Controllers\CreateLocationController;
use App\Location\Controllers\DeleteLocationController;
use App\Location\Controllers\EditLocationController;
use App\Location\Controllers\StoreLocationController;
use App\Location\Controllers\UpdateLocationController;
use App\Onboarding\Controllers\RemoveSampleDataController;
use App\Onboarding\Middleware\EnsureOnboarded;
use App\Service\Controllers\CreateServiceController;
use App\Service\Controllers\DeleteServiceController;
use App\Service\Controllers\EditServiceController;
use App\Service\Controllers\StoreServiceController;
use App\Service\Controllers\UpdateServiceController;
use App\Ticket\Controllers\ArchiveTicketController;
use App\Ticket\Controllers\CloseTicketController;
use App\Ticket\Controllers\CreateTicketController;
use App\Ticket\Controllers\EditTicketController;
use App\Ticket\Controllers\ListTicketsController;
use App\Ticket\Controllers\MoveTicketController;
use App\Ticket\Controllers\ReopenTicketController;
use App\Ticket\Controllers\RestoreTicketController;
use App\Ticket\Controllers\ShowTicketController;
use App\Ticket\Controllers\StoreTicketController;
use App\Ticket\Controllers\UpdateTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureOnboarded::class])->group(function () {
    Route::get('customers', ListCustomersController::class)->name('customers.index');
    Route::get('customers/create', CreateCustomerController::class)->name('customers.create');
    Route::post('customers', StoreCustomerController::class)->name('customers.store');
    Route::get('customers/import', ShowImportCustomersController::class)->name('customers.import');
    Route::post('customers/import', ImportCustomersController::class)->name('customers.import.store');
    Route::delete('sample-data', RemoveSampleDataController::class)->name('sample-data.destroy');
    Route::get('customers/{customer}', ShowCustomerController::class)->whereUuid('customer')->name('customers.show');
    Route::get('customers/{customer}/edit', EditCustomerController::class)->whereUuid('customer')->name('customers.edit');
    Route::put('customers/{customer}', UpdateCustomerController::class)->whereUuid('customer')->name('customers.update');
    Route::delete('customers/{customer}', DeleteCustomerController::class)->whereUuid('customer')->name('customers.destroy');

    Route::get('contacts', ListContactsController::class)->name('contacts.index');
    Route::get('contacts/create', CreateContactController::class)->name('contacts.create');
    Route::post('contacts', StoreContactController::class)->name('contacts.store');
    Route::get('contacts/{contact}/edit', EditContactController::class)->whereUuid('contact')->name('contacts.edit');
    Route::put('contacts/{contact}', UpdateContactController::class)->whereUuid('contact')->name('contacts.update');
    Route::delete('contacts/{contact}', DeleteContactController::class)->whereUuid('contact')->name('contacts.destroy');

    Route::get('customers/{customer}/locations/create', CreateLocationController::class)->whereUuid('customer')->name('locations.create');
    Route::post('customers/{customer}/locations', StoreLocationController::class)->whereUuid('customer')->name('locations.store');
    Route::get('locations/{location}/edit', EditLocationController::class)->whereUuid('location')->name('locations.edit');
    Route::put('locations/{location}', UpdateLocationController::class)->whereUuid('location')->name('locations.update');
    Route::delete('locations/{location}', DeleteLocationController::class)->whereUuid('location')->name('locations.destroy');

    Route::get('customers/{customer}/assets/create', CreateAssetController::class)->whereUuid('customer')->name('assets.create');
    Route::post('customers/{customer}/assets', StoreAssetController::class)->whereUuid('customer')->name('assets.store');
    Route::get('assets/{asset}/edit', EditAssetController::class)->whereUuid('asset')->name('assets.edit');
    Route::put('assets/{asset}', UpdateAssetController::class)->whereUuid('asset')->name('assets.update');
    Route::delete('assets/{asset}', DeleteAssetController::class)->whereUuid('asset')->name('assets.destroy');

    Route::get('customers/{customer}/services/create', CreateServiceController::class)->whereUuid('customer')->name('services.create');
    Route::post('customers/{customer}/services', StoreServiceController::class)->whereUuid('customer')->name('services.store');
    Route::get('services/{service}/edit', EditServiceController::class)->whereUuid('service')->name('services.edit');
    Route::put('services/{service}', UpdateServiceController::class)->whereUuid('service')->name('services.update');
    Route::delete('services/{service}', DeleteServiceController::class)->whereUuid('service')->name('services.destroy');

    Route::get('tickets', ListTicketsController::class)->name('tickets.index');
    Route::get('tickets/create', CreateTicketController::class)->name('tickets.create');
    Route::post('tickets', StoreTicketController::class)->name('tickets.store');
    Route::get('tickets/{ticket}', ShowTicketController::class)->whereUuid('ticket')->name('tickets.show');
    Route::get('tickets/{ticket}/edit', EditTicketController::class)->whereUuid('ticket')->name('tickets.edit');
    Route::put('tickets/{ticket}', UpdateTicketController::class)->whereUuid('ticket')->name('tickets.update');
    Route::post('tickets/{ticket}/close', CloseTicketController::class)->whereUuid('ticket')->name('tickets.close');
    Route::post('tickets/{ticket}/reopen', ReopenTicketController::class)->whereUuid('ticket')->name('tickets.reopen');
    Route::put('tickets/{ticket}/move', MoveTicketController::class)->whereUuid('ticket')->name('tickets.move');
    Route::post('tickets/{ticket}/archive', ArchiveTicketController::class)->whereUuid('ticket')->name('tickets.archive');
    Route::post('tickets/{ticket}/restore', RestoreTicketController::class)->whereUuid('ticket')->name('tickets.restore');

    Route::post('favorites', ToggleFavoriteController::class)->name('favorites.toggle');

    Route::get('invoices', ListInvoicesController::class)->name('invoices.index');
    Route::get('invoices/create', CreateInvoiceController::class)->name('invoices.create');
    Route::post('invoices', StoreInvoiceController::class)->name('invoices.store');
    Route::get('invoices/{invoice}', ShowInvoiceController::class)->whereUuid('invoice')->name('invoices.show');
    Route::get('invoices/{invoice}/edit', EditInvoiceController::class)->whereUuid('invoice')->name('invoices.edit');
    Route::put('invoices/{invoice}', UpdateInvoiceController::class)->whereUuid('invoice')->name('invoices.update');
    Route::post('invoices/{invoice}/sent', MarkInvoiceSentController::class)->whereUuid('invoice')->name('invoices.sent');
    Route::post('invoices/{invoice}/paid', MarkInvoicePaidController::class)->whereUuid('invoice')->name('invoices.paid');
    Route::post('invoices/{invoice}/void', VoidInvoiceController::class)->whereUuid('invoice')->name('invoices.void');
});
