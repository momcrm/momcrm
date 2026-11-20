<?php

declare(strict_types=1);

namespace App\BankRule\Controllers;

use App\BankRule\FormRequests\UpdateBankRuleFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateBankRuleController
{
    public function __invoke(UpdateBankRuleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
