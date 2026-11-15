<?php

declare(strict_types=1);

namespace App\Invoice\FormRequests;

use App\Invoice\Actions\FindInvoice;
use App\Invoice\Actions\UpdateInvoice;
use App\Invoice\Exceptions\CouldNotFindInvoice;
use App\Invoice\Invoice;
use App\Invoice\InvoiceValidationRules;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class UpdateInvoiceFormRequest extends FormRequest
{
    public Invoice $invoice;

    public function __construct()
    {
        parent::__construct();

        $this->invoice = Invoice::new();
    }

    /**
     * @throws CouldNotFindInvoice
     */
    public function authorize(): Response
    {
        $this->invoice = FindInvoice::byIdForTeam((string) $this->route('invoice'), $this->user()?->current_team_id);

        if ($this->invoice->isNull()) {
            throw CouldNotFindInvoice::notFound();
        }

        if (! $this->invoice->isDraft()) {
            return Response::deny(__('Only draft invoices can be edited.'));
        }

        return Gate::authorize('update', $this->invoice);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return InvoiceValidationRules::for($this, $this->invoice->getCustomerId()->toString());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return InvoiceValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();

        $invoice = UpdateInvoice::fromInvoice(
            $this->invoice->setPropertyIfExists($validated),
            InvoiceValidationRules::items($validated),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':reference saved.', ['reference' => $invoice->getReference()])]);

        return to_route('invoices.show', $invoice->getId()->toString());
    }

    protected function prepareForValidation(): void
    {
        InvoiceValidationRules::normalise($this);
    }
}
