<?php

declare(strict_types=1);

namespace App\AccessRule;

use App\AccessRule\Factories\AccessRuleFactory;
use App\AccessRule\Properties\Actions;
use App\AccessRule\Properties\Conditions;
use App\AccessRule\Properties\CreatedAt;
use App\AccessRule\Properties\CreatedById;
use App\AccessRule\Properties\Effect;
use App\AccessRule\Properties\Id;
use App\AccessRule\Properties\IsEnabled;
use App\AccessRule\Properties\Name;
use App\AccessRule\Properties\Resources;
use App\AccessRule\Properties\TeamId;
use App\AccessRule\Properties\UpdatedAt;
use App\Shared\Types;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A rule a team's owners and admins write to allow or deny actions on records,
 * when all of its conditions hold. See AccessEvaluator for how rules combine.
 *
 * @extends AbstractData<AccessRuleEloquentModel>
 */
final class AccessRule extends AbstractData
{
    public function __construct(
        public Actions $actions,
        public Conditions $conditions,
        public CreatedAt $createdAt,
        public CreatedById $createdById,
        public Effect $effect,
        public Id $id,
        public IsEnabled $isEnabled,
        public Name $name,
        public Resources $resources,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): AccessRuleFactory
    {
        return AccessRuleEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): AccessRuleCollection
    {
        if ($items instanceof AccessRuleCollection) {
            return $items;
        }

        return AccessRuleCollection::make(parent::collect($items, $method)->all());
    }

    public function getActions(): Actions
    {
        return $this->actions;
    }

    public function setActions(mixed $actions): self
    {
        $this->actions = new Actions($actions instanceof AbstractValue ? $actions->toValue() : $actions);

        return $this;
    }

    public function getConditions(): Conditions
    {
        return $this->conditions;
    }

    public function setConditions(mixed $conditions): self
    {
        $this->conditions = new Conditions($conditions instanceof AbstractValue ? $conditions->toValue() : $conditions);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getCreatedById(): CreatedById
    {
        return $this->createdById;
    }

    public function setCreatedById(mixed $createdById): self
    {
        $this->createdById = new CreatedById($createdById instanceof AbstractValue ? $createdById->toValue() : $createdById);

        return $this;
    }

    public function getEffect(): Effect
    {
        return $this->effect;
    }

    public function setEffect(mixed $effect): self
    {
        $this->effect = new Effect($effect instanceof AbstractValue ? $effect->toValue() : $effect);

        return $this;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

        return $this;
    }

    public function getIsEnabled(): IsEnabled
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(mixed $isEnabled): self
    {
        $this->isEnabled = new IsEnabled($isEnabled instanceof AbstractValue ? $isEnabled->toValue() : $isEnabled);

        return $this;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(mixed $name): self
    {
        $this->name = new Name($name instanceof AbstractValue ? $name->toValue() : $name);

        return $this;
    }

    public function getResources(): Resources
    {
        return $this->resources;
    }

    public function setResources(mixed $resources): self
    {
        $this->resources = new Resources($resources instanceof AbstractValue ? $resources->toValue() : $resources);

        return $this;
    }

    public function getTeamId(): TeamId
    {
        return $this->teamId;
    }

    public function setTeamId(mixed $teamId): self
    {
        $this->teamId = new TeamId($teamId instanceof AbstractValue ? $teamId->toValue() : $teamId);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }

    /**
     * @return array{id: string, name: string, resources: array<int, string>, actions: array<int, string>, effect: string, conditions: array<int, array{attribute: ?string, operator: ?string, value: string|array<int, string>|null}>, is_enabled: bool}
     */
    public function toListItem(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'name' => $this->getName()->toString(),
            'resources' => Types::stringList($this->getResources()->toArray()),
            'actions' => Types::stringList($this->getActions()->toArray()),
            'effect' => $this->getEffect()->toEnum()->value,
            'conditions' => array_map(fn (AccessCondition $condition): array => $condition->toArray(), $this->conditionObjects()),
            'is_enabled' => $this->getIsEnabled()->toBoolean(),
        ];
    }

    /**
     * The resources the rule covers; empty covers every resource.
     *
     * @return array<int, AccessResourceEnum>
     */
    public function resourceEnums(): array
    {
        return $this->getResources()->toCollection()
            ->map(fn (mixed $value): ?AccessResourceEnum => is_string($value) ? AccessResourceEnum::tryFrom($value) : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * The actions the rule covers; empty covers every action.
     *
     * @return array<int, AccessActionEnum>
     */
    public function actionEnums(): array
    {
        return $this->getActions()->toCollection()
            ->map(fn (mixed $value): ?AccessActionEnum => is_string($value) ? AccessActionEnum::tryFrom($value) : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, AccessCondition>
     */
    public function conditionObjects(): array
    {
        return $this->getConditions()->toCollection()
            ->map(fn (mixed $condition): AccessCondition => AccessCondition::fromArray(is_array($condition) ? $condition : []))
            ->values()
            ->all();
    }

    /**
     * Whether the rule covers this action on this resource. A rule that names
     * resources or actions the code no longer knows covers none of them.
     */
    public function covers(AccessResourceEnum $resource, AccessActionEnum $action): bool
    {
        $resources = $this->getResources()->toCollection();
        $actions = $this->getActions()->toCollection();

        return ($resources->isEmpty() || $resources->contains($resource->value))
            && ($actions->isEmpty() || $actions->contains($action->value));
    }
}
