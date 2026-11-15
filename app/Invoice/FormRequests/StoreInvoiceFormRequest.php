<?php

declare(strict_types=1);

namespace App\Invoice\FormRequests;

use App\Invoice\Actions\CreateInvoice;
use App\Invoice\Invoice;
use App\Invoice\InvoiceValidationRules;
use App\Invoice\Properties\CustomerId;
use App\Shared\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class StoreInvoiceFormRequest extends FormRequest
{
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
            CustomerId::getNameForValidation() => [
                ...CustomerId::getValidationRulesForCreate($this),
                Rule::exists(Table::Customers->value, 'id')->where('team_id', $this->user()?->current_team_id),
            ],
            ...InvoiceValidationRules::for($this, $this->string(CustomerId::getNameForValidation())->toString()),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            CustomerId::getNameForValidation().'.required' => __('Choose the customer to invoice.'),
            CustomerId::getNameForValidation().'.exists' => __('Choose the customer to invoice.'),
            ...InvoiceValidationRules::messages(),
        ];
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();

        $invoice = CreateInvoice::fromInvoice(
            Invoice::new()->setPropertyIfExists($validated)->setTeamId($this->user()?->current_team_id),
            InvoiceValidationRules::items($validated),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':reference saved as a draft.', ['reference' => $invoice->getReference()])]);

        return to_route('invoices.show', $invoice->getId()->toString());
    }

    protected function prepareForValidation(): void
    {
        InvoiceValidationRules::normalise($this);
    }
}
