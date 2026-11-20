<?php

declare(strict_types=1);

namespace App\Invoice\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Invoice\Actions\DraftCustomerInvoice;
use App\Invoice\Billing\BillingLine;
use App\Invoice\Billing\BillingMonth;
use App\Invoice\Billing\CustomerBillingLines;
use App\Invoice\Invoice;
use App\Shared\Types;
use App\Team\Actions\FindTeam;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Bills a customer now, as Amphora's Multiple Service Billing and Combine do:
 * drafts one invoice, issued today, from the lines picked out of what they
 * are due for the month given (periods starting in or before it, and their
 * waiting lines).
 * Lines are named by their keys and priced again here, so what the page
 * showed isn't trusted. The new invoice's id is flashed as `invoiceId`.
 */
final class BillCustomerFormRequest extends FormRequest
{
    public const string LINE_KEYS = 'line_keys';

    public Customer $customer;

    /** @var list<BillingLine> */
    public array $lines = [];

    public function __construct()
    {
        parent::__construct();

        $this->customer = Customer::new();
    }

    /**
     * @throws CouldNotFindCustomer
     */
    public function authorize(): Response
    {
        $this->customer = FindCustomer::byIdForTeam((string) $this->route('customer'), $this->user()?->current_team_id);

        if ($this->customer->isNull()) {
            throw CouldNotFindCustomer::notFound();
        }

        return Gate::authorize('create', Invoice::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m'],
            self::LINE_KEYS => ['required', 'array', 'min:1'],
            self::LINE_KEYS.'.*' => ['required', 'string', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            self::LINE_KEYS.'.required' => __('Pick at least one line to bill.'),
            self::LINE_KEYS.'.min' => __('Pick at least one line to bill.'),
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $picked = Types::stringList($this->input(self::LINE_KEYS));
            $due = CustomerBillingLines::forCustomer($this->customer->getId()->toString(), BillingMonth::fromString($this->string('month')->toString())->lastDay());
            $this->lines = array_values(array_filter($due, fn (BillingLine $line): bool => in_array($line->key, $picked, true)));

            if (count($this->lines) !== count($picked)) {
                $validator->errors()->add(self::LINE_KEYS, __('Some of those lines were billed or changed since. Look again.'));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $invoice = DraftCustomerInvoice::from(
            $this->customer,
            FindTeam::byId($this->customer->getTeamId()->toString()),
            $this->lines,
            CarbonImmutable::today(),
        );

        Inertia::flash([
            'toast' => ['type' => 'success', 'message' => __(':reference drafted for :customer.', ['reference' => $invoice->getReference(), 'customer' => $this->customer->getName()->toString()])],
            'invoiceId' => $invoice->getId()->toString(),
        ]);

        return back();
    }
}
