<?php

declare(strict_types=1);

namespace App\BankAccount\Controllers;

use App\BankAccount\Actions\FindBankAccount;
use App\BankAccount\BankAccountAccess;
use App\BankAccount\BankAccountKindEnum;
use App\BankRule\Actions\FindBankRule;
use App\BankRule\BankRuleKindEnum;
use App\Customer\Actions\FindCustomer;
use App\Shared\CurrencyEnum;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The team's bank accounts and bank feed rules, for owners and admins to set
 * up before statements are imported.
 */
final class ListBankAccountsController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        BankAccountAccess::authorize($request);

        $teamId = BankAccountAccess::team($request)->getId()->toString();

        return Inertia::render('settings/bank-feeds', [
            'accounts' => FindBankAccount::listForTeam($teamId)->toSummaries(),
            'rules' => FindBankRule::listForTeam($teamId)->toSummaries(),
            'ruleKinds' => BankRuleKindEnum::options(),
            'customers' => FindCustomer::optionsForTeam($teamId)->values()->all(),
            'kinds' => BankAccountKindEnum::options(),
            'currencies' => CurrencyEnum::options(),
        ]);
    }
}
