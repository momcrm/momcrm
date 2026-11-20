<?php

declare(strict_types=1);

namespace App\BankAccount\Controllers;

use App\BankAccount\FormRequests\StoreBankAccountFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreBankAccountController
{
    public function __invoke(StoreBankAccountFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
