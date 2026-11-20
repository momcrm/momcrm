<?php

declare(strict_types=1);

namespace App\Portal\FormRequests;

use App\AuditLog\Actions\RecordAudit;
use App\AuditLog\AuditEventEnum;
use App\Contact\Actions\FindContact;
use App\Contact\Contact;
use App\Contact\ContactEloquentModel;
use App\Contact\Exceptions\CouldNotFindContact;
use App\Portal\PortalAuth;
use App\Portal\PortalSession;
use App\Team\Actions\FindTeam;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

/**
 * Opens the customer portal as one of the team's contacts, for an owner or
 * admin to check what they see. Nothing can be changed while viewing, and
 * the visit is recorded in the contact's audit log.
 */
final class ViewPortalAsContactFormRequest extends FormRequest
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
        $teamId = $this->user()?->current_team_id;
        $this->contact = FindContact::byIdForTeam((string) $this->route('contact'), $teamId);

        if ($this->contact->isNull()) {
            throw CouldNotFindContact::notFound();
        }

        return Gate::authorize('viewPortalAsContact', FindTeam::byId($teamId));
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
            if (! $this->contact->getPortalAccess()->toBoolean()) {
                $validator->errors()->add('portal_access', __(':name has no portal access to view.', ['name' => $this->contact->getFullName()]));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $model = ContactEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->contact->getId()->toString());

        RecordAudit::fromModel($model, AuditEventEnum::Viewed);

        Auth::guard(PortalAuth::GUARD)->login($model);
        $this->session()->put(PortalSession::VIEWING_AS, (string) $this->user()?->id);

        return to_route('portal.home');
    }
}
