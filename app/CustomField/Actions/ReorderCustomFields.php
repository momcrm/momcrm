<?php

declare(strict_types=1);

namespace App\CustomField\Actions;

use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\Properties\Position;
use App\CustomField\Properties\Target;
use App\CustomField\Properties\TeamId;
use Illuminate\Support\Facades\DB;

/**
 * Puts a team's fields on one kind of record in the given order, top first.
 */
final readonly class ReorderCustomFields
{
    /**
     * @param  list<string>  $ids
     */
    public function __construct(
        private string $teamId,
        private CustomFieldTargetEnum $target,
        private array $ids,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            foreach ($this->ids as $position => $id) {
                CustomFieldEloquentModel::query()
                    ->withoutGlobalScopes()
                    ->where(TeamId::getDatabaseTableColumnName(), $this->teamId)
                    ->where(Target::getDatabaseTableColumnName(), $this->target->value)
                    ->whereKey($id)
                    ->first()
                    ?->update([Position::getDatabaseTableColumnName() => $position]);
            }
        });

        FindCustomField::forget($this->teamId);
    }

    /**
     * @param  list<string>  $ids
     */
    public static function forTeam(string $teamId, CustomFieldTargetEnum $target, array $ids): void
    {
        new self($teamId, $target, $ids)->handle();
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
