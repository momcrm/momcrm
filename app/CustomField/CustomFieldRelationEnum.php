<?php

declare(strict_types=1);

namespace App\CustomField;

use App\Contact\Properties\FirstName;
use App\Contact\Properties\LastName;
use App\Customer\Properties\Name as CustomerName;
use App\Shared\Table;
use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;
use App\Shared\Types;
use App\TeamUser\Properties\TeamId as MembershipTeamId;
use App\TeamUser\Properties\UserId as MembershipUserId;
use App\User\Properties\Name as UserName;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The kinds of record a "record link" custom field can point at. Each knows
 * how to find the team's records of its kind and what to call them.
 */
enum CustomFieldRelationEnum: string
{
    use Enum;
    use EnumOptions;

    case Member = 'member';
    case Customer = 'customer';
    case Contact = 'contact';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'A team member',
            self::Customer => 'A customer',
            self::Contact => 'A contact',
        };
    }

    /**
     * The names of the team's records with these ids, by id. Ids that are
     * not the team's, or no longer exist, are left out.
     *
     * @param  list<string>  $ids
     * @return array<string, string>
     */
    public function names(string $teamId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->named($this->records($teamId)->whereIn($this->idColumn(), $ids));
    }

    /**
     * The team's records whose name contains the search, as picker options,
     * by name. Everything, up to the limit, when the search is empty.
     *
     * @return list<array{value: string, label: string}>
     */
    public function search(string $teamId, string $search, int $limit = 20): array
    {
        $query = $this->records($teamId)->limit($limit);

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                foreach ($this->nameColumns() as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        foreach ($this->nameColumns() as $column) {
            $query->orderBy($column);
        }

        $options = [];

        foreach ($this->named($query) as $id => $name) {
            $options[] = ['value' => $id, 'label' => $name];
        }

        return $options;
    }

    private function records(string $teamId): Builder
    {
        return match ($this) {
            self::Member => DB::table(Table::Users->value)
                ->join(Table::TeamUser->value, MembershipUserId::getDatabaseTableColumnNameWithTable(Table::TeamUser->value), '=', $this->idColumn())
                ->where(MembershipTeamId::getDatabaseTableColumnNameWithTable(Table::TeamUser->value), $teamId),
            self::Customer => DB::table(Table::Customers->value)->where('team_id', $teamId),
            self::Contact => DB::table(Table::Contacts->value)->where('team_id', $teamId),
        };
    }

    private function idColumn(): string
    {
        return match ($this) {
            self::Member => Table::Users->value.'.id',
            self::Customer => Table::Customers->value.'.id',
            self::Contact => Table::Contacts->value.'.id',
        };
    }

    /**
     * The columns a record's name is made of, in order.
     *
     * @return list<string>
     */
    private function nameColumns(): array
    {
        return match ($this) {
            self::Member => [UserName::getDatabaseTableColumnNameWithTable(Table::Users->value)],
            self::Customer => [CustomerName::getDatabaseTableColumnNameWithTable(Table::Customers->value)],
            self::Contact => [
                FirstName::getDatabaseTableColumnNameWithTable(Table::Contacts->value),
                LastName::getDatabaseTableColumnNameWithTable(Table::Contacts->value),
            ],
        };
    }

    /**
     * @return array<string, string>
     */
    private function named(Builder $query): array
    {
        $columns = $this->nameColumns();
        $select = ["{$this->idColumn()} as record_id"];

        foreach ($columns as $index => $column) {
            $select[] = "{$column} as name_{$index}";
        }

        $names = [];

        foreach ($query->get($select) as $row) {
            $parts = [];

            foreach (array_keys($columns) as $index) {
                $parts[] = Types::nullableString($row->{"name_{$index}"}) ?? '';
            }

            $names[Types::string($row->record_id)] = trim(implode(' ', $parts));
        }

        return $names;
    }
}
