<?php

declare(strict_types=1);

namespace App\Choice\Actions;

use App\AccessRule\Actions\PruneDeletedRecordsFromAccessRules;
use App\Choice\Choice;
use App\Choice\ChoiceEloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a choice for good, and tidies the access rule conditions that named
 * it. Records that still use it lose it: a ticket's label is taken off. (Only
 * optional lists, such as labels, allow deleting a choice that is in use.)
 */
final readonly class DeleteChoice
{
    public function __construct(
        private Choice $choice,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            $id = $this->choice->getId()->toString();

            foreach ($this->choice->getListName()->toEnum()->usages() as $usage) {
                $records = DB::table($usage['table']->value)->where($usage['column'], $id);

                $usage['isPivot'] ? $records->delete() : $records->update([$usage['column'] => null]);
            }

            ChoiceEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->choice->getId()->toString())->delete();

            PruneDeletedRecordsFromAccessRules::forTeam($this->choice->getTeamId()->toString());
        });
    }

    public static function fromChoice(Choice $choice): void
    {
        new self($choice)->handle();
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
