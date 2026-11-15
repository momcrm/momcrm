<?php

declare(strict_types=1);

namespace App\Choice\Actions;

use App\Choice\ChoiceEloquentModel;
use App\Choice\ChoiceListEnum;
use App\Choice\Properties\ListName;
use App\Choice\Properties\Position;
use App\Choice\Properties\TeamId;
use Illuminate\Support\Facades\DB;

/**
 * Puts a team's choices in one list in the given order, top first. For
 * priorities the order is also their rank.
 */
final readonly class ReorderChoices
{
    /**
     * @param  list<string>  $ids
     */
    public function __construct(
        private string $teamId,
        private ChoiceListEnum $list,
        private array $ids,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            foreach ($this->ids as $position => $id) {
                ChoiceEloquentModel::query()
                    ->withoutGlobalScopes()
                    ->where(TeamId::getDatabaseTableColumnName(), $this->teamId)
                    ->where(ListName::getDatabaseTableColumnName(), $this->list->value)
                    ->whereKey($id)
                    ->first()
                    ?->update([Position::getDatabaseTableColumnName() => $position]);
            }
        });
    }

    /**
     * @param  list<string>  $ids
     */
    public static function forTeam(string $teamId, ChoiceListEnum $list, array $ids): void
    {
        new self($teamId, $list, $ids)->handle();
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
