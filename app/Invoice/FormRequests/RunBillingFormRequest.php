<?php

declare(strict_types=1);

namespace App\Invoice\FormRequests;

use App\Invoice\Actions\DraftCustomerInvoice;
use App\Invoice\Billing\BillingLine;
use App\Invoice\BillingRun;
use App\Invoice\Invoice;
use App\Shared\Types;
use App\Team\Actions\FindTeam;
use App\Team\Team;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Drafts the invoices of a billing run: for each customer picked, one draft
 * from the lines picked, priced again here. If anything changed since the
 * page was shown, nothing is drafted. The drafts' ids are flashed as
 * `draftedInvoiceIds`, to send them next.
 */
final class RunBillingFormRequest extends FormRequest
{
    public const string CUSTOMERS = 'customers';

    /** @var array<string, list<BillingLine>> */
    public array $picked = [];

    public Team $team;

    public function __construct()
    {
        parent::__construct();

        $this->team = Team::new();
    }

    public function authorize(): Response
    {
        return Gate::authorize('create', Invoice::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            self::CUSTOMERS => ['required', 'array', 'min:1'],
            self::CUSTOMERS.'.*' => ['required', 'array', 'min:1'],
            self::CUSTOMERS.'.*.*' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            self::CUSTOMERS.'.required' => __('Pick at least one customer to bill.'),
            self::CUSTOMERS.'.min' => __('Pick at least one customer to bill.'),
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

            $this->team = FindTeam::byId((string) $this->user()?->current_team_id);
            $run = BillingRun::for($this->team, $this->day());
            $input = $this->input(self::CUSTOMERS);

            foreach (is_array($input) ? $input : [] as $customerId => $keys) {
                $due = $run->lines[(string) $customerId] ?? [];
                $keys = Types::stringList($keys);
                $lines = array_values(array_filter($due, fn (BillingLine $line): bool => in_array($line->key, $keys, true)));

                if (count($lines) !== count(array_unique($keys))) {
                    $validator->errors()->add(self::CUSTOMERS, __('Some lines were billed or changed since. Look again before drafting.'));

                    return;
                }

                $this->picked[(string) $customerId] = $lines;
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $run = BillingRun::for($this->team, $this->day());

        $ids = DB::transaction(function () use ($run): array {
            $ids = [];

            foreach ($this->picked as $customerId => $lines) {
                $ids[] = DraftCustomerInvoice::from($run->customers[$customerId], $this->team, $lines, $this->day())->getId()->toString();
            }

            return $ids;
        });

        Inertia::flash([
            'toast' => ['type' => 'success', 'message' => trans_choice(':count invoice drafted.|:count invoices drafted.', count($ids))],
            'draftedInvoiceIds' => $ids,
        ]);

        return to_route('invoices.run', ['date' => $this->day()->toDateString()]);
    }

    private function day(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('date')->toString())->startOfDay();
    }
}
