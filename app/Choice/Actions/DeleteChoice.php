<?php

declare(strict_types=1);

namespace App\Choice\Actions;

use App\Choice\Choice;
use App\Choice\ChoiceEloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a choice, first moving the records that use it (such as tickets in
 * a status) to the replacement.
 */
final readonly class DeleteChoice
{
    public function __construct(
        private Choice $choice,
        private ?string $replacementId,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            $id = $this->choice->getId()->toString();

            foreach ($this->choice->getListName()->toEnum()->usages() as $usage) {
                DB::table($usage['table']->value)
                    ->where('team_id', $this->choice->getTeamId()->toString())
                    ->where($usage['column'], $id)
                    ->update([$usage['column'] => $this->replacementId]);
            }

            ChoiceEloquentModel::query()->withoutGlobalScopes()->findOrFail($id)->delete();
        });
    }

    public static function fromChoice(Choice $choice, ?string $replacementId): void
    {
        new self($choice, $replacementId)->handle();
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
