<?php

declare(strict_types=1);

namespace App\Contact\FormRequests;

use App\Contact\Actions\DeleteContact;
use App\Contact\Actions\FindContact;
use App\Contact\Contact;
use App\Contact\Exceptions\CouldNotFindContact;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class DeleteContactFormRequest extends FormRequest
{
    public Contact $contact;

    public function __construct()
    {
        parent::__construct();

        $this->contact = Contact::new();
    }

    /**
     * @throws CouldNotFindContact
     */
    public function authorize(): Response
    {
        $this->contact = FindContact::byIdForTeam((string) $this->route('contact'), $this->user()?->current_team_id);

        if ($this->contact->isNull()) {
            throw CouldNotFindContact::notFound();
        }

        return Gate::authorize('delete', $this->contact);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        DeleteContact::fromContact($this->contact);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $this->contact->getFullName()])]);

        return to_route('customers.show', $this->contact->getCustomerId()->toString());
    }
}
