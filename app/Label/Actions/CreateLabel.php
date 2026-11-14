<?php

declare(strict_types=1);

namespace App\Label\Actions;

use App\Label\Label;
use App\Label\LabelEloquentModel;
use App\Label\Properties\Id;
use App\Shared\ColorEnum;

final readonly class CreateLabel
{
    public function __construct(
        private Label $label,
    ) {}

    public function __invoke(): Label
    {
        if ($this->label->getId()->isNull()) {
            $this->label->setId(Id::generate());
        }

        if ($this->label->getColor()->isNull()) {
            $this->label->setColor(ColorEnum::Slate);
        }

        $model = LabelEloquentModel::query()->create($this->label->forDatabaseCreate());

        return Label::fromEloquentModel($model->refresh());
    }

    public static function fromLabel(Label $label): Label
    {
        return new self($label)->handle();
    }

    public function handle(): Label
    {
        return $this->__invoke();
    }

    public function execute(): Label
    {
        return $this->__invoke();
    }
}
