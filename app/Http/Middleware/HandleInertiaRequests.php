<?php

namespace App\Http\Middleware;

use App\Favorite\Actions\FindFavorite;
use App\Favorite\FavoritableTypeEnum;
use App\Onboarding\Actions\GetGettingStartedProgress;
use App\Onboarding\Actions\RemoveSampleData;
use App\Project\Actions\FindProject;
use App\Team\Actions\FindTeam;
use App\Team\Team;
use App\Ticket\Actions\FindTicket;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
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
            'sidebarCounts' => fn (): ?array => $this->sidebarCounts($request),
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
