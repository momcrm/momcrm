<?php

declare(strict_types=1);

namespace App\BankTransaction\Controllers;

use App\BankTransaction\FormRequests\ImportBankStatementFormRequest;
use Illuminate\Http\RedirectResponse;

final class ImportBankStatementController
{
    public function __invoke(ImportBankStatementFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
