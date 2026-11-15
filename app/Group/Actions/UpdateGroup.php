<?php

declare(strict_types=1);

namespace App\Group\Actions;

use App\Group\Group;
use App\Group\GroupEloquentModel;

/**
 * Saves a group's name, description, icon and colour.
 */
final readonly class UpdateGroup
{
    public function __construct(
        private Group $group,
    ) {}

    public function __invoke(): void
    {
        GroupEloquentModel::query()
            ->withoutGlobalScopes()
            ->find($this->group->getId()->toString())
            ?->update($this->group->forDatabaseUpdate());
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
