<?php

declare(strict_types=1);

namespace App\CustomField\Factories;

use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\CustomField\Properties\ArchivedAt;
use App\CustomField\Properties\Name;
use App\CustomField\Properties\Options;
use App\CustomField\Properties\Position;
use App\CustomField\Properties\Relation;
use App\CustomField\Properties\Target;
use App\CustomField\Properties\TeamId;
use App\CustomField\Properties\Type;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomFieldEloquentModel>
 */
final class CustomFieldFactory extends Factory
{
    protected $model = CustomFieldEloquentModel::class;

    /**
     * A text field on tickets, unless a state says otherwise.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Target::getDatabaseTableColumnName() => CustomFieldTargetEnum::Ticket->value,
            Type::getDatabaseTableColumnName() => CustomFieldTypeEnum::Text->value,
            Name::getDatabaseTableColumnName() => fake()->unique()->words(2, true),
            Options::getDatabaseTableColumnName() => [],
            Position::getDatabaseTableColumnName() => 0,
        ];
    }

    public function on(CustomFieldTargetEnum $target): self
    {
        return $this->state([Target::getDatabaseTableColumnName() => $target->value]);
    }

    public function ofType(CustomFieldTypeEnum $type): self
    {
        return $this->state([Type::getDatabaseTableColumnName() => $type->value]);
    }

    /**
     * A select offering these options, in order.
     *
     * @param  list<string>  $names
     */
    public function select(array $names = ['Low', 'High'], bool $isMultiple = false): self
    {
        return $this->state([
            Type::getDatabaseTableColumnName() => ($isMultiple ? CustomFieldTypeEnum::MultiSelect : CustomFieldTypeEnum::Select)->value,
            Options::getDatabaseTableColumnName() => array_map(
                fn (string $name): array => ['id' => (string) Str::uuid7(), 'name' => $name, 'hue' => null],
                $names,
            ),
        ]);
    }

    /**
     * A link to one of the team's records of this kind.
     */
    public function linkingTo(CustomFieldRelationEnum $relation): self
    {
        return $this->state([
            Type::getDatabaseTableColumnName() => CustomFieldTypeEnum::Relation->value,
            Relation::getDatabaseTableColumnName() => $relation->value,
        ]);
    }

    public function archived(): self
    {
        return $this->state([ArchivedAt::getDatabaseTableColumnName() => now()]);
    }
}
