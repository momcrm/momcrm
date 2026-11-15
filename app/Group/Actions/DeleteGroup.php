<?php

declare(strict_types=1);

namespace App\Group\Actions;

use App\Group\Group;
use App\Group\GroupEloquentModel;

/**
 * Deletes a group. Its projects stay, without a group.
 */
final readonly class DeleteGroup
{
    public function __construct(
        private Group $group,
    ) {}

    public function __invoke(): void
    {
        GroupEloquentModel::query()
            ->withoutGlobalScopes()
            ->find($this->group->getId()->toString())
            ?->delete();
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
