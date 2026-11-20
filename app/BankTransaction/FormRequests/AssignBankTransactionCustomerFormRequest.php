<?php

declare(strict_types=1);

namespace App\BankTransaction\FormRequests;

use App\BankAccount\BankAccountAccess;
use App\BankRule\Actions\CreateBankRule;
use App\BankRule\BankRule;
use App\BankRule\BankRuleKindEnum;
use App\BankRule\Properties\Contains;
use App\BankTransaction\Actions\AssignBankTransactionCustomer;
use App\BankTransaction\Actions\FindBankTransaction;
use App\BankTransaction\BankTransaction;
use App\BankTransaction\Exceptions\CouldNotFindBankTransaction;
use App\Customer\Actions\FindCustomer;
use App\Shared\Types;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Picks the customer a pending bank feed line is from, by hand, or clears it.
 * Owners and admins can also have it remembered (`remember`): a match rule
 * for the text given, so later deposits like it find the customer alone.
 */
final class AssignBankTransactionCustomerFormRequest extends FormRequest
{
    public BankTransaction $transaction;

    public function __construct()
    {
        parent::__construct();

        $this->transaction = BankTransaction::new();
    }

    /**
     * @throws CouldNotFindBankTransaction
     */
    public function authorize(): Response
    {
        $this->transaction = FindBankTransaction::byIdForTeam((string) $this->route('bankTransaction'), $this->user()?->current_team_id);

        if ($this->transaction->isNull()) {
            throw CouldNotFindBankTransaction::notFound();
        }

        return Gate::authorize('update', $this->transaction);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['present', 'nullable', 'uuid'],
            'remember' => ['sometimes', 'nullable', ...array_slice(Contains::getValidationRulesForCreate($this), 1)],
        ];
    }

    /**
     * The customer must be the team's and billed in the account's currency,
     * since the money can only pay their invoices in it.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! $this->transaction->isPending()) {
                $validator->errors()->add('customer_id', __('Only a pending line’s customer can change.'));

                return;
            }

            $customerId = Types::nullableString($this->input('customer_id'));

            if ($this->filled('remember') && ($customerId === null || Gate::denies('manageBankFeeds', BankAccountAccess::team($this)))) {
                $validator->errors()->add('remember', __('Only team owners and admins can add bank feed rules.'));
            }

            if ($customerId === null) {
                return;
            }

            $customer = FindCustomer::byIdForTeam($customerId, $this->user()?->current_team_id);
            $currency = $this->transaction->getBankAccount()->toData()->getCurrency()->toEnum();

            if ($customer->isNull()) {
                $validator->errors()->add('customer_id', __('Choose one of your customers.'));
            } elseif ($customer->getCurrency()->toEnum() !== $currency) {
                $validator->errors()->add('customer_id', __(':name is billed in :theirs, but this money is in :account.', [
                    'name' => $customer->getName()->toString(),
                    'theirs' => $customer->getCurrency()->toEnum()->value,
                    'account' => $currency->value,
                ]));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $customerId = Types::nullableString($this->input('customer_id'));

        AssignBankTransactionCustomer::to($this->transaction, $customerId);

        if ($this->filled('remember')) {
            CreateBankRule::fromBankRule(
                BankRule::new()
                    ->setTeamId((string) $this->user()?->current_team_id)
                    ->setKind(BankRuleKindEnum::Match->value)
                    ->setContains($this->string('remember')->squish()->toString())
                    ->setCustomerId($customerId),
            );

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Deposits containing “:text” will be matched to this customer.', ['text' => $this->string('remember')->squish()->toString()])]);
        }

        return back();
    }
}
