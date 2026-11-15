<?php

declare(strict_types=1);

namespace App\Choice\Actions;

use App\Choice\Choice;
use App\Choice\ChoiceEloquentModel;
use App\Choice\Properties\Id;
use App\Shared\ColorEnum;

final readonly class CreateChoice
{
    public function __construct(
        private Choice $choice,
    ) {}

    public function __invoke(): Choice
    {
        if ($this->choice->getId()->isNull()) {
            $this->choice->setId(Id::generate());
        }

        if ($this->choice->getColor()->isNull()) {
            $this->choice->setColor(ColorEnum::Slate);
        }

        $model = ChoiceEloquentModel::query()->create($this->choice->forDatabaseCreate());

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
