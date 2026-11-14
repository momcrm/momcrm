<?php

declare(strict_types=1);

namespace App\TeamInvitation\FormRequests;

use App\Shared\Table;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotUpdateTeam;
use App\Team\Team;
use App\TeamInvitation\Actions\CreateTeamInvitation;
use App\TeamInvitation\Properties\Email;
use App\TeamInvitation\Properties\Role;
use App\TeamUser\Properties\TeamId;
use App\TeamUser\TeamRoleEnum;
use App\User\Properties\Email as UserEmail;
use App\User\User;
use App\User\UserEloquentModel;
use App\User\UserRelationship;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Invites one or more email addresses, typed as a comma- or space-separated list.
 */
final class StoreTeamInvitationsFormRequest extends FormRequest
{
    public const int MAX_INVITES = 20;

    public Team $team;

    public function __construct()
    {
        parent::__construct();

        $this->team = Team::new();
    }

    /**
     * @throws CouldNotUpdateTeam
     */
    public function authorize(): Response
    {
        $this->team = FindTeam::byIdForMember(
            id: $this->user()?->current_team_id,
            userId: (string) $this->user()?->getAuthIdentifier(),
        );

        if ($this->team->isNull()) {
            throw CouldNotUpdateTeam::notFound();
        }

        return Gate::authorize('invite', $this->team);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'emails' => ['required', 'string'],
            'email_list' => ['required', 'array', 'max:'.self::MAX_INVITES],
            'email_list.*' => Email::getValidationRulesForCreate($this),
            Role::getNameForValidation() => Role::getValidationRulesForCreate($this),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'emails.required' => __('Add at least one email address.'),
            'email_list.required' => __('Add at least one email address.'),
            'email_list.max' => __('You can invite up to :max people at a time.', ['max' => self::MAX_INVITES]),
            'email_list.*.email' => __(':input is not a valid email address.'),
        ];
    }

    /**
     * Sends the invitations, skipping addresses that already belong to the team.
     */
    public function process(string $redirectRoute): RedirectResponse
    {
        /** @var array<int, string> $emails */
        $emails = $this->validated('email_list');
        $role = TeamRoleEnum::from((string) $this->validated(Role::getNameForValidation()));
        $inviter = User::fromEloquentModel($this->user());

        $members = UserEloquentModel::query()
            ->whereIn(UserEmail::getDatabaseTableColumnName(), $emails)
            ->whereHas(UserRelationship::Teams->value, fn ($query) => $query->where(TeamId::getDatabaseTableColumnNameWithTable(Table::TeamUser), $this->team->getId()->toString()))
            ->pluck(UserEmail::getDatabaseTableColumnName())
            ->map(fn (string $email): string => mb_strtolower($email))
            ->all();

        $invited = array_values(array_diff($emails, $members));

        foreach ($invited as $email) {
            CreateTeamInvitation::forTeam(team: $this->team, email: $email, role: $role, inviter: $inviter);
        }

        Inertia::flash('toast', match (true) {
            $invited === [] => ['type' => 'info', 'message' => __('Everyone on that list is already in the team.')],
            count($invited) === 1 => ['type' => 'success', 'message' => __('Invite sent to :email.', ['email' => $invited[0]])],
            default => ['type' => 'success', 'message' => __(':count invites sent.', ['count' => count($invited)])],
        });

        return to_route($redirectRoute);
    }

    protected function prepareForValidation(): void
    {
        $emails = preg_split('/[\s,;]+/', (string) $this->input('emails'), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $this->merge([
            'email_list' => array_values(array_unique(array_map(mb_strtolower(...), $emails))),
            Role::getNameForValidation() => $this->input(Role::getNameForValidation(), TeamRoleEnum::Member->value),
        ]);
    }
}
