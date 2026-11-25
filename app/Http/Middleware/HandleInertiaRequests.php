<?php

namespace App\Http\Middleware;

use App\Attendance\AttendanceClock;
use App\Auth\Actions\FindProvenEmail;
use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Conversation\Actions\FindConversation;
use App\CustomField\Actions\FindCustomField;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomObject\Actions\FindCustomObject;
use App\Environment\EnvironmentVariables;
use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Group\Actions\FindGroup;
use App\Inbox\Actions\FindNotifications;
use App\Inbox\Events\InboxChanged;
use App\Onboarding\Actions\GetGettingStartedProgress;
use App\Onboarding\Actions\RemoveSampleData;
use App\Portal\Actions\FindPortalContacts;
use App\Portal\PortalAuth;
use App\Portal\PortalProps;
use App\Project\Actions\FindProject;
use App\Shared\SettingsNavigation;
use App\Team\Actions\FindTeam;
use App\Team\SuspendedTeamAction;
use App\Team\Team;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Events\TicketChanged;
use App\Ticket\TeamTicketsChannel;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\LiveProp;
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
     * Every open page shows the sidebar, so a busy team's ticket changes
     * reload its counts at most this often.
     */
    private const int SIDEBAR_COUNTS_THROTTLE_MS = 2000;

    public function __construct(
        private readonly SettingsNavigation $settingsNavigation,
        private readonly SuspendedTeamAction $suspendedTeamAction,
    ) {}

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
        if (PortalAuth::isServingContactPage($request)) {
            return $this->sharePortal($request);
        }

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
            'canManageAttendance' => fn (): bool => $this->allowsOnCurrentTeam($request, 'manageAttendance'),
            'canManageBankFeeds' => fn (): bool => $this->allowsOnCurrentTeam($request, 'manageBankFeeds'),
            'canManageBilling' => fn (): bool => $this->allowsOnCurrentTeam($request, 'manageBilling'),
            'canManageConnectors' => fn (): bool => $this->allowsOnCurrentTeam($request, 'manageConnectors'),
            'canManageCustomFields' => fn (): bool => $this->allowsOnCurrentTeam($request, 'manageCustomFields'),
            'canManageCustomObjects' => fn (): bool => $this->allowsOnCurrentTeam($request, 'manageCustomObjects'),
            'canManageEmail' => fn (): bool => $this->allowsOnCurrentTeam($request, 'manageEmail'),
            'canManageRates' => fn (): bool => $this->allowsOnCurrentTeam($request, 'manageRates'),
            'canManageWorkflow' => fn (): bool => $this->allowsOnCurrentTeam($request, 'manageWorkflow'),
            'canManageEnvironment' => fn (): bool => $request->user() instanceof UserEloquentModel
                && Gate::forUser($request->user())->allows('manage', EnvironmentVariables::class),
            'settingsNavigation' => fn (): array => $this->settingsNavigation->for($request),
            'suspendedTeamAction' => fn (): ?array => $this->suspendedTeamAction($request),
            'sidebarCounts' => $this->liveSidebarCounts($request),
            'choices' => fn (): ?array => $this->choices($request),
            'attendanceClock' => fn (): ?array => $this->attendanceClock($request),
            'customFields' => fn (): ?array => $this->customFields($request),
            'canOpenPortal' => fn (): bool => FindPortalContacts::existForEmail(FindProvenEmail::forRequest($request)),
            'gettingStarted' => fn (): ?array => $request->user() instanceof UserEloquentModel
                ? GetGettingStartedProgress::forUser($request->user())
                : null,
        ];
    }

    /**
     * The customer portal shares nothing about the team's own workspace, even
     * to a staff member viewing it as a contact.
     *
     * @return array<string, mixed>
     */
    private function sharePortal(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'portal' => fn (): ?array => PortalProps::forSignedIn(),
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
     * Whether the signed-in member is clocked in or on a break, for the
     * clock in the header.
     *
     * @return array{shift_id: ?string, clocked_in_at: ?string, on_break_since: ?string, break_seconds: int}|null
     */
    private function attendanceClock(Request $request): ?array
    {
        $user = $request->user();

        if (! $user instanceof UserEloquentModel || ! is_string($user->current_team_id)) {
            return null;
        }

        return AttendanceClock::forMember($user->current_team_id, $user->id);
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

        return collect(CustomFieldTargetEnum::builtIn())
            ->mapWithKeys(fn (CustomFieldTargetEnum $target): array => [$target->value => FindCustomField::listForTeam($teamId, $target)->toSummaries()])
            ->all();
    }

    /**
     * The sidebar's numbers, reloaded when the person's inbox or any of the
     * team's tickets change.
     */
    private function liveSidebarCounts(Request $request): ?LiveProp
    {
        $user = $request->user();

        if (! $user instanceof UserEloquentModel || ! is_string($user->current_team_id)) {
            return null;
        }

        return InboxChanged::liveFor($user->id, fn (): ?array => $this->sidebarCounts($request), throttle: self::SIDEBAR_COUNTS_THROTTLE_MS)
            ->live(on: TicketChanged::class, channel: TeamTicketsChannel::for($user->current_team_id));
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
            'unreadMessages' => FindConversation::countUnreadMessages($user->current_team_id, $user->id),
            'groups' => FindGroup::optionsForTeam($user->current_team_id),
            'objects' => FindCustomObject::listForTeam($user->current_team_id)->toSummaries(),
            'projects' => FindProject::forSidebar(
                $user->current_team_id,
                $user->id,
                FindFavorite::idsForUser($user->current_team_id, $user->id, FavoritableTypeEnum::Project),
            ),
        ];
    }

    /**
     * @return array{id: string, name: string, is_suspended: bool}|null
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
     * What the signed-in member can do about their current team being suspended.
     *
     * @return ?array{label: string, href: string}
     */
    private function suspendedTeamAction(Request $request): ?array
    {
        $user = $request->user();

        if (! $user instanceof UserEloquentModel) {
            return null;
        }

        $team = FindTeam::byIdForMember(id: $user->current_team_id, userId: $user->id);

        return $team->isNull() ? null : $this->suspendedTeamAction->for($request, $team);
    }

    /**
     * @return array<int, array{id: string, name: string, is_suspended: bool}>
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
