<?php

declare(strict_types=1);

namespace App\Portal\FormRequests;

use App\Auth\Actions\FindProvenEmail;
use App\Contact\Contact;
use App\Portal\Actions\FindPortalContacts;
use App\Portal\Actions\LogInContact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Signs in as the chosen contact, which must be one of the portal contacts
 * for the email address just proven.
 */
final class ChoosePortalContactFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        $contact = FindPortalContacts::forEmail(FindProvenEmail::forRequest($this))
            ->first(fn (Contact $contact): bool => $contact->getId()->equalsTo($this->route('contact')));

        if ($contact->isNull()) {
            throw new NotFoundHttpException;
        }

        return LogInContact::as($this, $contact);
    }
}
