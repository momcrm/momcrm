<?php

declare(strict_types=1);

namespace App\Group\Actions;

use App\Group\Group;
use App\Group\GroupEloquentModel;
use App\Group\GroupRoleEnum;
use App\Group\Properties\Id;
use Illuminate\Support\Facades\DB;

/**
 * Creates a group. The lead, if given, joins as its lead; the other members
 * join as members.
 */
final readonly class CreateGroup
{
    /**
     * @param  array<int, string>  $memberIds
     */
    public function __construct(
        private Group $group,
        private ?string $leadId = null,
        private array $memberIds = [],
    ) {}

    public function __invoke(): Group
    {
        return DB::transaction(function (): Group {
            if ($this->group->getId()->isNull()) {
                $this->group->setId(Id::generate());
            }

            $model = GroupEloquentModel::query()->create($this->group->forDatabaseCreate());

            $members = collect($this->memberIds)
                ->reject(fn (string $id): bool => $id === $this->leadId)
                ->mapWithKeys(fn (string $id): array => [$id => ['role' => GroupRoleEnum::Member->value]]);

            if ($this->leadId !== null) {
                $members->put($this->leadId, ['role' => GroupRoleEnum::Lead->value]);
            }

            $model->members()->sync($members->all());

            return Group::fromEloquentModel($model->refresh());
        });
    }

    /**
     * @param  array<int, string>  $memberIds
     */
    public static function fromGroup(Group $group, ?string $leadId = null, array $memberIds = []): Group
    {
        return new self($group, $leadId, $memberIds)->handle();
    }

    public function handle(): Group
    {
        return $this->__invoke();
    }

    public function execute(): Group
    {
        return $this->__invoke();
    }
}
