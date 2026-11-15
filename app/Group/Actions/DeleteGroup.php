<?php

declare(strict_types=1);

namespace App\Group\Actions;

use App\AccessRule\Actions\PruneDeletedRecordsFromAccessRules;
use App\Group\Group;
use App\Group\GroupEloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a group. Its projects stay, without a group, and access rule
 * conditions that named it are tidied up.
 */
final readonly class DeleteGroup
{
    public function __construct(
        private Group $group,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            GroupEloquentModel::query()
                ->withoutGlobalScopes()
                ->find($this->group->getId()->toString())
                ?->delete();

            PruneDeletedRecordsFromAccessRules::forTeam($this->group->getTeamId()->toString());
        });
    }

    public static function fromGroup(Group $group): void
    {
        new self($group)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
