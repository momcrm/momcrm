<?php

declare(strict_types=1);

namespace App\Customer\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Customer\Actions\UpdateCustomer;
use App\Customer\Customer;
use App\Customer\CustomerCollection;
use App\Customer\CustomerValidationRules;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Customer\Properties\BillingType;
use App\Customer\Properties\Status;
use App\Customer\Properties\TypeId;
use App\Shared\Types;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Sets the type, billing type or status of several customers at once, from
 * the bar the customer list shows once customers are picked. Each customer
 * is authorized as if it were changed on its own, and if any one is refused,
 * none change.
 */
final class BulkUpdateCustomersFormRequest extends FormRequest
{
    public const string CUSTOMER_IDS = 'customer_ids';

    public const int MAX = 200;

    /**
     * The fields a bulk change can set.
     *
     * @var array<int, string>
     */
    public const array FIELDS = ['type_id', 'billing_type', 'status'];

    public CustomerCollection $customers;

    public function __construct()
    {
        parent::__construct();

        $this->customers = new CustomerCollection([]);
    }

    /**
     * @throws CouldNotFindCustomer
     */
    public function authorize(): Response
    {
        $input = $this->input(self::CUSTOMER_IDS);
        $ids = array_values(array_unique(array_filter(
            is_array($input) ? $input : [],
            fn (mixed $id): bool => is_string($id) && Str::isUuid($id),
        )));

        if ($ids === [] || count($ids) > self::MAX) {
            return Response::allow();
        }

        $this->customers = FindCustomer::byIdsForTeam((string) $this->user()?->current_team_id, $ids);

        if ($this->customers->count() !== count($ids)) {
            throw CouldNotFindCustomer::notFound();
        }

        foreach ($this->customers as $customer) {
            Gate::authorize('update', $customer);
        }

        return Response::allow();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $fields = CustomerValidationRules::for($this, (string) $this->user()?->current_team_id);

        return [
            self::CUSTOMER_IDS => ['required', 'array', 'min:1', 'max:'.self::MAX],
            self::CUSTOMER_IDS.'.*' => ['uuid', 'distinct'],
            TypeId::getNameForValidation() => ['sometimes', ...$fields[TypeId::getNameForValidation()]],
            BillingType::getNameForValidation() => $fields[BillingType::getNameForValidation()],
            Status::getNameForValidation() => ['sometimes', ...$fields[Status::getNameForValidation()]],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return CustomerValidationRules::messages();
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->hasAny(self::FIELDS)) {
                $validator->errors()->add(self::CUSTOMER_IDS, __('Choose what to change on the selected customers.'));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $changes = Types::stringKeyed(Arr::only($this->validated(), self::FIELDS));

        DB::transaction(function () use ($changes): void {
            $this->customers->each(fn (Customer $customer): Customer => UpdateCustomer::fromCustomer($customer->setPropertyIfExists($changes)));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice('{1} 1 customer updated.|[2,*] :count customers updated.', $this->customers->count())]);

        return back();
    }
}
