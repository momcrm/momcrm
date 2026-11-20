<?php

declare(strict_types=1);

namespace App\BankAccount\Controllers;

use App\BankAccount\FormRequests\DeleteBankAccountFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteBankAccountController
{
    public function __invoke(DeleteBankAccountFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
