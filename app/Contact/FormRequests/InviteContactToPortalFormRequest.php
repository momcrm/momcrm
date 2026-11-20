<?php

declare(strict_types=1);

namespace App\Contact\FormRequests;

use App\Contact\Actions\FindContact;
use App\Contact\Actions\UpdateContact;
use App\Contact\Contact;
use App\Contact\Exceptions\CouldNotFindContact;
use App\Contact\Notifications\PortalInvitationNotification;
use App\Customer\Actions\FindCustomer;
use App\Team\Actions\FindTeam;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Lets a contact into the customer portal, if they weren't already, and
 * emails them an invitation with a link to sign in. Sending it again is a
 * reminder.
 */
final class InviteContactToPortalFormRequest extends FormRequest
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

        return Gate::authorize('update', $this->contact);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->contact->getEmail()->isNull()) {
                $validator->errors()->add('email', __('Add an email address for :name first.', ['name' => $this->contact->getFullName()]));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $email = $this->contact->getEmail()->toString();
        $teamId = $this->contact->getTeamId()->toString();

        UpdateContact::fromContact($this->contact->setPortalAccess(true)->setPortalInvitedAt(CarbonImmutable::now()));

        Notification::route('mail', $email)->notify(new PortalInvitationNotification(
            teamName: FindTeam::byId($teamId)->getName()->toString(),
            customerName: FindCustomer::byIdForTeam($this->contact->getCustomerId()->toString(), $teamId)->getName()->toString(),
            email: $email,
            invitedByName: $this->user()?->name,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent to :email.', ['email' => $email])]);

        return back();
    }
}
