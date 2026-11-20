<?php

declare(strict_types=1);

namespace App\BankAccount\Controllers;

use App\BankAccount\FormRequests\UpdateBankAccountFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateBankAccountController
{
    public function __invoke(UpdateBankAccountFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
