<?php

declare(strict_types=1);

namespace App\CustomObject\Actions;

use App\CustomObject\CustomObjectEloquentModel;
use App\CustomObject\Properties\Position;
use App\CustomObject\Properties\TeamId;
use Illuminate\Support\Facades\DB;

/**
 * Puts a team's objects in the given order, top first: the order of the
 * sidebar.
 */
final readonly class ReorderCustomObjects
{
    /**
     * @param  list<string>  $ids
     */
    public function __construct(
        private string $teamId,
        private array $ids,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            foreach ($this->ids as $position => $id) {
                CustomObjectEloquentModel::query()
                    ->withoutGlobalScopes()
                    ->where(TeamId::getDatabaseTableColumnName(), $this->teamId)
                    ->whereKey($id)
                    ->first()
                    ?->update([Position::getDatabaseTableColumnName() => $position]);
            }
        });

        FindCustomObject::forget($this->teamId);
    }

    /**
     * @param  list<string>  $ids
     */
    public static function forTeam(string $teamId, array $ids): void
    {
        new self($teamId, $ids)->handle();
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
