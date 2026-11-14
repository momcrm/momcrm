<?php

use App\Contact\Controllers\CreateContactController;
use App\Contact\Controllers\DeleteContactController;
use App\Contact\Controllers\EditContactController;
use App\Contact\Controllers\ListContactsController;
use App\Contact\Controllers\StoreContactController;
use App\Contact\Controllers\UpdateContactController;
use App\Customer\Controllers\CreateCustomerController;
use App\Customer\Controllers\DeleteCustomerController;
use App\Customer\Controllers\EditCustomerController;
use App\Customer\Controllers\ListCustomersController;
use App\Customer\Controllers\ShowCustomerController;
use App\Customer\Controllers\StoreCustomerController;
use App\Customer\Controllers\UpdateCustomerController;
use App\Onboarding\Middleware\EnsureOnboarded;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureOnboarded::class])->group(function () {
    Route::get('customers', ListCustomersController::class)->name('customers.index');
    Route::get('customers/create', CreateCustomerController::class)->name('customers.create');
    Route::post('customers', StoreCustomerController::class)->name('customers.store');
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
});
