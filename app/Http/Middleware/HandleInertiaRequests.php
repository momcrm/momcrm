<?php

namespace App\Http\Middleware;

use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\CustomField\Actions\FindCustomField;
use App\CustomField\CustomFieldTargetEnum;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Group\Actions\FindGroup;
use App\Inbox\Actions\FindNotifications;
use App\Onboarding\Actions\GetGettingStartedProgress;
use App\Onboarding\Actions\RemoveSampleData;
use App\Project\Actions\FindProject;
use App\Team\Actions\FindTeam;
use App\Team\Team;
use App\Ticket\Actions\FindTicket;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'currentTeam' => fn (): ?array => $this->currentTeam($request),
                'teams' => fn (): array => $this->teams($request),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'hasSampleData' => fn (): bool => $this->hasSampleData($request),
            'canRemoveSampleData' => fn (): bool => $this->canRemoveSampleData($request),
            'canManageAccessRules' => fn (): bool => $this->allowsOnCurrentTeam($request, 'manageAccessRules'),
            'canManageCustomFields' => fn (): bool => $this->allowsOnCurrentTeam($request, 'manageCustomFields'),
            'canManageCustomObjects' => fn (): bool => $this->allowsOnCurrentTeam($request, 'manageCustomObjects'),
            'canManageWorkflow' => fn (): bool => $this->allowsOnCurrentTeam($request, 'manageWorkflow'),
            'sidebarCounts' => fn (): ?array => $this->sidebarCounts($request),
            'choices' => fn (): ?array => $this->choices($request),
            'customFields' => fn (): ?array => $this->customFields($request),
            'gettingStarted' => fn (): ?array => $request->user() instanceof UserEloquentModel
                ? GetGettingStartedProgress::forUser($request->user())
                : null,
        ];
    }

    /**
     * Whether the current team still has the sample customers added during onboarding.
     */
    private function hasSampleData(Request $request): bool
    {
        $teamId = $request->user()?->current_team_id;

        return is_string($teamId) && RemoveSampleData::teamHasSampleData($teamId);
    }

    private function canRemoveSampleData(Request $request): bool
    {
        return $this->allowsOnCurrentTeam($request, 'removeSampleData');
    }

    /**
     * Whether the signed-in person may take a TeamPolicy action on their current team.
     */
    private function allowsOnCurrentTeam(Request $request, string $ability): bool
    {
        $user = $request->user();

        if (! $user instanceof UserEloquentModel) {
            return false;
        }

        $team = FindTeam::byIdForMember(id: $user->current_team_id, userId: $user->id);

        return $team->isNotNull() && Gate::forUser($user)->allows($ability, $team);
    }

    /**
     * The current team's customisable lists, such as ticket priorities, keyed
     * by list, so any page can show a ticket's priority from its id.
     *
     * @return array<string, list<array{id: string, name: string, hue: ?int, icon: ?string, category: ?string, position: int, is_default: bool}>>|null
     */
    private function choices(Request $request): ?array
    {
        $teamId = $request->user()?->current_team_id;

        if (! is_string($teamId)) {
            return null;
        }

        return collect(ChoiceListEnum::cases())
            ->mapWithKeys(fn (ChoiceListEnum $list): array => [$list->value => FindChoice::listForTeam($teamId, $list, withArchived: true)->toSummaries()])
            ->all();
    }

    /**
     * The fields the current team has added to its records, keyed by kind of
     * record, so any page can show and edit a record's custom fields.
     *
     * @return array<string, list<array<string, mixed>>>|null
     */
    private function customFields(Request $request): ?array
    {
        $teamId = $request->user()?->current_team_id;

        if (! is_string($teamId)) {
            return null;
        }

        return collect(CustomFieldTargetEnum::cases())
            ->mapWithKeys(fn (CustomFieldTargetEnum $target): array => [$target->value => FindCustomField::listForTeam($teamId, $target)->toSummaries()])
            ->all();
    }

    /**
     * The numbers shown next to sidebar items.
     *
     * @return array{openTickets: int, myOpenTickets: int}|null
     */
    private function sidebarCounts(Request $request): ?array
    {
        $user = $request->user();

        if (! $user instanceof UserEloquentModel || ! is_string($user->current_team_id)) {
            return null;
        }

        return [
            'openTickets' => FindTicket::countOpenForTeam($user->current_team_id),
            'myOpenTickets' => FindTicket::countOpenForTeam($user->current_team_id, $user->id),
            'unreadNotifications' => FindNotifications::countUnread($user),
            'groups' => FindGroup::optionsForTeam($user->current_team_id),
            'projects' => FindProject::forSidebar(
                $user->current_team_id,
                $user->id,
                FindFavorite::idsForUser($user->current_team_id, $user->id, FavoritableTypeEnum::Project),
            ),
        ];
    }

    /**
     * @return array{id: string, name: string}|null
     */
    private function currentTeam(Request $request): ?array
    {
        $user = $request->user();

        if (! $user instanceof UserEloquentModel) {
            return null;
        }

        $team = FindTeam::byIdForMember(id: $user->current_team_id, userId: $user->id);

        return $team->isNull() ? null : $team->toSummary();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    private function teams(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof UserEloquentModel) {
            return [];
        }

        return FindTeam::allForMember($user->id)
            ->map(fn (Team $team): array => $team->toSummary())
            ->all();
    }
}
