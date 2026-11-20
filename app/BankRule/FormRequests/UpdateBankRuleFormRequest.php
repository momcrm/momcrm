<?php

declare(strict_types=1);

namespace App\BankRule\FormRequests;

use App\BankAccount\BankAccountAccess;
use App\BankRule\Actions\FindBankRule;
use App\BankRule\Actions\UpdateBankRule;
use App\BankRule\BankRule;
use App\BankRule\BankRuleValidationRules;
use App\BankRule\Exceptions\CouldNotFindBankRule;
use App\Shared\Types;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Changes a bank feed rule.
 */
final class UpdateBankRuleFormRequest extends FormRequest
{
    public BankRule $rule;

    public function __construct()
    {
        parent::__construct();

        $this->rule = BankRule::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindBankRule
     */
    public function authorize(): Response
    {
        $this->rule = FindBankRule::byIdForTeam((string) $this->route('bankRule'), BankAccountAccess::team($this)->getId()->toString());

        if ($this->rule->isNull()) {
            throw CouldNotFindBankRule::notFound();
        }

        return BankAccountAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return BankRuleValidationRules::for($this);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return BankRuleValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        UpdateBankRule::fromBankRule(
            $this->rule
                ->setPropertyIfExists(Types::stringKeyed($this->validated()))
                ->setCustomerId($this->validated('customer_id')),
        );

        return back();
    }
}
