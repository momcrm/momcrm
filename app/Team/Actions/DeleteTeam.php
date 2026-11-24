<?php

declare(strict_types=1);

namespace App\Team\Actions;

use App\Attachment\Actions\CreateAttachment;
use App\Team\Properties\Id;
use App\Team\Team;
use App\Team\TeamEloquentModel;
use App\TeamUser\Properties\TeamId;
use App\TeamUser\Properties\UserId;
use App\TeamUser\TeamUserEloquentModel;
use App\User\User;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Deletes a team and everything in it, for good: its customers, tickets,
 * invoices, settings, memberships and the files it uploaded. There is no way
 * back, and no page in the app does this; it is for whoever runs the
 * installation.
 *
 * Every account keeps a team to work in: a member who was in no other team
 * gets a new, empty personal team, as a new account does. The others fall
 * back to one of their remaining teams the next time they open the app.
 */
final readonly class DeleteTeam
{
    public function __construct(
        private Team $team,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(): void
    {
        $teamId = $this->team->getId()->toString();

        DB::transaction(function () use ($teamId): void {
            $memberIds = TeamUserEloquentModel::query()
                ->withoutGlobalScopes()
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->pluck(UserId::getDatabaseTableColumnName());

            // Deleted as a query, not a model: every table that belongs to a
            // team cascades from this row, the audit log among them, so there
            // is nowhere left to record the deletion in.
            TeamEloquentModel::query()->where(Id::getDatabaseTableColumnName(), $teamId)->delete();

            UserEloquentModel::query()
                ->whereKey($memberIds)
                ->whereNotIn('id', TeamUserEloquentModel::query()->withoutGlobalScopes()->select(UserId::getDatabaseTableColumnName()))
                ->get()
                ->each(fn (UserEloquentModel $member) => CreatePersonalTeam::fromUser(User::fromEloquentModel($member)));
        });

        Storage::disk(CreateAttachment::DISK)->deleteDirectory("attachments/{$teamId}");
    }

    /**
     * @throws Throwable
     */
    public static function fromTeam(Team $team): void
    {
        new self($team)->handle();
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->__invoke();
    }

    /**
     * @throws Throwable
     */
    public function execute(): void
    {
        $this->__invoke();
    }
}
