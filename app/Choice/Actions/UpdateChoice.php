<?php

declare(strict_types=1);

namespace App\Choice\Actions;

use App\Choice\Choice;
use App\Choice\ChoiceEloquentModel;

/**
 * Saves a choice's name, colour, icon or place in its list.
 */
final readonly class UpdateChoice
{
    public function __construct(
        private Choice $choice,
    ) {}

    public function __invoke(): Choice
    {
        $model = ChoiceEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->choice->getId()->toString());
        $model->update($this->choice->forDatabaseUpdate());

        return Choice::fromEloquentModel($model->refresh());
    }

    public static function fromChoice(Choice $choice): Choice
    {
        return new self($choice)->handle();
    }

    public function handle(): Choice
    {
        return $this->__invoke();
    }

    public function execute(): Choice
    {
        return $this->__invoke();
    }
}
