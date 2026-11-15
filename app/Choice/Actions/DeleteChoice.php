<?php

declare(strict_types=1);

namespace App\Choice\Actions;

use App\AccessRule\Actions\PruneDeletedChoicesFromAccessRules;
use App\Choice\Choice;
use App\Choice\ChoiceEloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a choice for good. Only archived choices that nothing uses are
 * deleted, so no record is left pointing at a missing choice, and access
 * rule conditions that named it are tidied up in the same step.
 */
final readonly class DeleteChoice
{
    public function __construct(
        private Choice $choice,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            ChoiceEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->choice->getId()->toString())->delete();

            PruneDeletedChoicesFromAccessRules::forTeam($this->choice->getTeamId()->toString());
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
