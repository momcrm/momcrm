<?php

declare(strict_types=1);

namespace App\Choice;

use App\Choice\Properties\Position;
use App\Shared\Table;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Gives a model the team's labels (choices from the `label` list), through
 * its own link table, in the list's order.
 */
trait HasLabels
{
    abstract public static function labelTarget(): LabelTargetEnum;

    /**
     * @return BelongsToMany<ChoiceEloquentModel, $this>
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(
            related: ChoiceEloquentModel::class,
            table: static::labelTarget()->table()->value,
            foreignPivotKey: static::labelTarget()->column(),
            relatedPivotKey: 'label_id',
            relation: 'labels',
        )->orderBy(Position::getDatabaseTableColumnNameWithTable(Table::Choices->value));
    }
}
