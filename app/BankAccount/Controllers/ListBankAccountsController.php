<?php

declare(strict_types=1);

namespace App\BankAccount\Controllers;

use App\BankAccount\Actions\FindBankAccount;
use App\BankAccount\BankAccountAccess;
use App\BankAccount\BankAccountKindEnum;
use App\Shared\CurrencyEnum;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The team's bank accounts, for owners and admins to set up before
 * statements are imported into them.
 */
final class ListBankAccountsController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        BankAccountAccess::authorize($request);

        return Inertia::render('settings/bank-feeds', [
            'accounts' => FindBankAccount::listForTeam(BankAccountAccess::team($request)->getId()->toString())->toSummaries(),
            'kinds' => BankAccountKindEnum::options(),
            'currencies' => CurrencyEnum::options(),
        ]);
    }
}
