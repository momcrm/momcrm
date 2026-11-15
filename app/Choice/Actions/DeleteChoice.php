<?php

declare(strict_types=1);

namespace App\Choice\Actions;

use App\Choice\Choice;
use App\Choice\ChoiceEloquentModel;

/**
 * Deletes a choice for good. Only archived choices that nothing uses are
 * deleted, so no record is left pointing at a missing choice.
 */
final readonly class DeleteChoice
{
    public function __construct(
        private Choice $choice,
    ) {}

    public function __invoke(): void
    {
        ChoiceEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->choice->getId()->toString())->delete();
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
