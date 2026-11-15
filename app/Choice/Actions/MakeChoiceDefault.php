<?php

declare(strict_types=1);

namespace App\Choice\Actions;

use App\Choice\Choice;
use App\Choice\ChoiceEloquentModel;
use App\Choice\Properties\Category;
use App\Choice\Properties\IsDefault;
use App\Choice\Properties\ListName;
use App\Choice\Properties\TeamId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Makes a choice the one new records start with, in place of its list's (or,
 * for a list with categories, its category's) current default.
 */
final readonly class MakeChoiceDefault
{
    public function __construct(
        private Choice $choice,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            ChoiceEloquentModel::query()
                ->withoutGlobalScopes()
                ->where(TeamId::getDatabaseTableColumnName(), $this->choice->getTeamId()->toString())
                ->where(ListName::getDatabaseTableColumnName(), $this->choice->getListName()->toEnum()->value)
                ->when(
                    $this->choice->getCategory()->toNullableString(),
                    fn (Builder $query, string $category) => $query->where(Category::getDatabaseTableColumnName(), $category),
                )
                ->where(IsDefault::getDatabaseTableColumnName(), true)
                ->get()
                ->each(fn (ChoiceEloquentModel $model): bool => $model->update([IsDefault::getDatabaseTableColumnName() => false]));

            UpdateChoice::fromChoice($this->choice->setIsDefault(true));
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
