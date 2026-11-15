<?php

declare(strict_types=1);

namespace App\Choice;

use App\Shared\Table;
use App\Shared\Traits\Enum;
use App\Shared\Types;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * The kinds of record that can have the team's labels (the `label` list),
 * each with its own link table, so a deleted record takes its links with it.
 */
enum LabelTargetEnum: string
{
    use Enum;

    /**
     * The form field holding the ids of the labels a record should have.
     */
    public const string FIELD = 'label_ids';

    /**
     * Sent by forms alongside the label toggles, so an empty selection (which
     * sends no label_ids at all) still means "no labels".
     */
    public const string SENT_FIELD = 'labels_sent';

    case Asset = 'asset';
    case Project = 'project';
    case Ticket = 'ticket';

    /**
     * The table linking labels to records of this kind.
     */
    public function table(): Table
    {
        return match ($this) {
            self::Asset => Table::LabelAsset,
            self::Project => Table::LabelProject,
            self::Ticket => Table::LabelTicket,
        };
    }

    /**
     * The link table's column holding the record's id.
     */
    public function column(): string
    {
        return "{$this->value}_id";
    }

    /**
     * The rule for one label id: one of the team's live labels, or one the
     * record already has, so it keeps an archived label when edited.
     */
    public function rule(string $teamId, ?string $recordId = null): Exists
    {
        return Rule::exists(Table::Choices->value, 'id')
            ->where('team_id', $teamId)
            ->where('list', ChoiceListEnum::Label->value)
            ->where(fn (Builder $query) => $query
                ->whereNull('archived_at')
                ->orWhereIn('id', DB::table($this->table()->value)->select('label_id')->where($this->column(), $recordId)));
    }

    /**
     * The validation rules for a form's labels.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(string $teamId, ?string $recordId = null): array
    {
        return [
            self::FIELD => ['sometimes', 'array'],
            self::FIELD.'.*' => ['uuid', $this->rule($teamId, $recordId)],
        ];
    }

    /**
     * The request's label fields, with a sent-but-empty selection as no
     * labels, to merge in before validation.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function sentEmpty(Request $request): array
    {
        return $request->boolean(self::SENT_FIELD) && ! $request->has(self::FIELD) ? [self::FIELD => []] : [];
    }

    /**
     * Gives a record exactly the labels in the validated data, when they were
     * sent; leaves them alone otherwise.
     *
     * @template TModel of Model
     *
     * @param  array<array-key, mixed>  $validated
     * @param  BelongsToMany<ChoiceEloquentModel, TModel>  $labels
     */
    public static function syncFrom(array $validated, BelongsToMany $labels): void
    {
        if (array_key_exists(self::FIELD, $validated)) {
            $labels->sync(Types::stringList($validated[self::FIELD]));
        }
    }

    /**
     * The ids of the labels a record has.
     *
     * @return list<string>
     */
    public function labelIds(string $recordId): array
    {
        return Types::stringList(DB::table($this->table()->value)->where($this->column(), $recordId)->pluck('label_id'));
    }
}
