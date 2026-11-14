<?php

declare(strict_types=1);

namespace App\Onboarding\Actions;

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Customer\Properties\Id as CustomerId;
use App\Customer\Properties\IsSample;
use App\Customer\Properties\TeamId as CustomerTeamId;
use App\Shared\Table;
use App\Team\Actions\FindTeam;
use App\Team\Scopes\CurrentTeamScope;
use App\TeamInvitation\Actions\FindTeamInvitation;
use App\TeamUser\Properties\TeamId as TeamUserTeamId;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * The getting-started checklist for the user's current team. Each step is
 * done when the thing it asks for exists; sample data does not count.
 */
final class GetGettingStartedProgress
{
    /**
     * @return array{steps: array<int, array{key: string, title: string, description: string, action: string, href: string, done: bool}>, completed: int, total: int}|null
     */
    public static function forUser(UserEloquentModel $user): ?array
    {
        if ($user->getting_started_dismissed_at !== null || $user->onboarded_at === null || $user->current_team_id === null) {
            return null;
        }

        $teamId = $user->current_team_id;
        $team = FindTeam::byIdForMember(id: $teamId, userId: $user->id);

        if ($team->isNull()) {
            return null;
        }

        $steps = [
            [
                'key' => 'customer',
                'title' => __('Add your first customer'),
                'description' => __("Start with a client you're working with this week."),
                'action' => __('Add customer'),
                'href' => route('customers.create'),
                'done' => self::realCustomers($teamId)->exists(),
            ],
            [
                'key' => 'contact',
                'title' => __('Add a contact'),
                'description' => __('Add the person you deal with at that customer.'),
                'action' => __('Add contact'),
                'href' => route('contacts.create'),
                'done' => self::forRealCustomers(ContactEloquentModel::query(), $teamId)->exists(),
            ],
            [
                'key' => 'ticket',
                'title' => __('Open a ticket'),
                'description' => __('Log a request or job so your team can pick it up.'),
                'action' => __('Open ticket'),
                'href' => route('tickets.create'),
                'done' => self::forRealCustomers(TicketEloquentModel::query(), $teamId)->exists(),
            ],
        ];

        if (Gate::forUser($user)->allows('invite', $team)) {
            $steps[] = [
                'key' => 'invite',
                'title' => __('Invite a teammate'),
                'description' => __('Work is easier with your team here.'),
                'action' => __('Invite people'),
                'href' => route('team.edit'),
                'done' => TeamUserEloquentModel::query()->where(TeamUserTeamId::getDatabaseTableColumnName(), $teamId)->count() > 1
                    || FindTeamInvitation::pendingForTeam($teamId)->isNotEmpty(),
            ];
        }

        $steps[] = [
            'key' => 'passkey',
            'title' => __('Secure your account'),
            'description' => __('Add a passkey so you can sign in with your face or fingerprint.'),
            'action' => __('Add passkey'),
            'href' => route('security.edit'),
            'done' => $user->passkeys()->exists(),
        ];

        $completed = count(array_filter($steps, fn (array $step): bool => $step['done']));

        return ['steps' => $steps, 'completed' => $completed, 'total' => count($steps)];
    }

    /**
     * @return Builder<CustomerEloquentModel>
     */
    private static function realCustomers(string $teamId): Builder
    {
        return CustomerEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(CustomerTeamId::getDatabaseTableColumnName(), $teamId)
            ->where(IsSample::getDatabaseTableColumnName(), false);
    }

    /**
     * Records in the team that belong to a customer who is not sample data.
     *
     * @template TModel of ContactEloquentModel|TicketEloquentModel
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private static function forRealCustomers(Builder $query, string $teamId): Builder
    {
        return $query
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where($query->qualifyColumn(CurrentTeamScope::TEAM_COLUMN), $teamId)
            ->whereIn($query->qualifyColumn('customer_id'), self::realCustomers($teamId)->select(CustomerId::getDatabaseTableColumnNameWithTable(Table::Customers)));
    }
}
