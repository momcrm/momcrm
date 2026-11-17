<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Choice\ChoiceEloquentModel;
use App\Choice\Properties\Id as ChoiceId;
use App\Choice\Properties\Position as ChoicePosition;
use App\CustomField\Actions\FindCustomField;
use App\CustomField\CustomFieldCollection;
use App\CustomField\CustomFieldQuery;
use App\CustomField\CustomFieldTargetEnum;
use App\Shared\Table;
use App\Ticket\Actions\FindTicket;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\GroupId;
use App\Ticket\Properties\Number;
use App\Ticket\Properties\PriorityId;
use App\Ticket\Properties\ProjectId;
use App\Ticket\Properties\StatusId;
use App\Ticket\Properties\Title;
use App\Ticket\Properties\TypeId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * What the ticket views are showing: the view itself, the filters picked in
 * the toolbar and the sort, read from the query string so links can be shared.
 */
final readonly class TicketListFilters
{
    public const string VIEW_BOARD = 'board';

    public const string VIEW_LIST = 'list';

    public const string VIEW_TABLE = 'table';

    public const string ASSIGNEE_ME = 'me';

    public const string NONE = 'none';

    /**
     * Columns the table can sort by, mapped to database columns.
     *
     * @var array<string, string>
     */
    public const array SORTS = [
        'number' => 'number',
        'title' => 'title',
        'status' => 'status',
        'priority' => 'priority',
        'due_on' => 'due_on',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
    ];

    /**
     * @param  array<int, string>  $priorities
     * @param  'asc'|'desc'  $direction
     * @param  array<string, string>  $customFields  Filters on custom fields, by field id.
     * @param  ?CustomFieldCollection  $fields  The team's live ticket fields, which the custom field filters and sort are read against.
     */
    public function __construct(
        public string $view = self::VIEW_LIST,
        public string $status = FindTicket::FILTER_OPEN,
        public ?string $search = null,
        public array $priorities = [],
        public ?string $assignee = null,
        public ?string $project = null,
        public ?string $label = null,
        public ?string $type = null,
        public ?string $group = null,
        public string $sort = 'number',
        public string $direction = 'desc',
        public array $customFields = [],
        public ?CustomFieldCollection $fields = null,
    ) {}

    /**
     * @param  ?string  $defaultStatus  The status when the query string sets none; the calendar shows every status.
     */
    public static function fromRequest(Request $request, ?string $defaultStatus = null): self
    {
        $view = in_array($request->query('view'), [self::VIEW_BOARD, self::VIEW_TABLE], true) ? (string) $request->query('view') : self::VIEW_LIST;
        $status = (string) $request->query('status', '');
        $sort = (string) $request->query('sort', 'number');
        $teamId = $request->user()?->current_team_id;
        $fields = is_string($teamId) ? FindCustomField::listForTeam($teamId, CustomFieldTargetEnum::Ticket) : CustomFieldCollection::make();

        return new self(
            view: $view,
            status: in_array($status, [FindTicket::FILTER_OPEN, FindTicket::FILTER_CLOSED, FindTicket::FILTER_ALL], true)
                ? $status
                : ($defaultStatus ?? ($view === self::VIEW_BOARD ? FindTicket::FILTER_ALL : FindTicket::FILTER_OPEN)),
            search: $request->string('search')->trim()->toString() ?: null,
            priorities: collect(explode(',', (string) $request->query('priority', '')))
                ->filter(fn (string $priority): bool => Str::isUuid($priority))
                ->values()
                ->all(),
            assignee: self::idOrKeyword($request->query('assignee'), [self::ASSIGNEE_ME, self::NONE]),
            project: self::idOrKeyword($request->query('project'), [self::NONE]),
            label: self::idOrKeyword($request->query('label'), []),
            type: self::idOrKeyword($request->query('type'), [self::NONE]),
            group: self::idOrKeyword($request->query('group'), [self::NONE]),
            sort: array_key_exists($sort, self::SORTS) || CustomFieldQuery::sortField($sort, $fields)->isNotNull() ? $sort : 'number',
            direction: $request->query('direction') === 'asc' ? 'asc' : 'desc',
            customFields: CustomFieldQuery::filtersFromRequest($request, $fields),
            fields: $fields,
        );
    }

    /**
     * The same filters, limited to one project (a project page's ticket tabs).
     */
    public function forProject(string $projectId): self
    {
        return new self(
            view: $this->view,
            status: $this->status,
            search: $this->search,
            priorities: $this->priorities,
            assignee: $this->assignee,
            project: $projectId,
            label: $this->label,
            type: $this->type,
            group: $this->group,
            sort: $this->sort,
            direction: $this->direction,
            customFields: $this->customFields,
            fields: $this->fields,
        );
    }

    /**
     * The same filters, limited to one assignee (the "My tickets" page).
     */
    public function forAssignee(string $assignee): self
    {
        return new self(
            view: $this->view,
            status: $this->status,
            search: $this->search,
            priorities: $this->priorities,
            assignee: $assignee,
            project: $this->project,
            label: $this->label,
            type: $this->type,
            group: $this->group,
            sort: $this->sort,
            direction: $this->direction,
            customFields: $this->customFields,
            fields: $this->fields,
        );
    }

    /**
     * @return array{view: string, status: string, search: ?string, priority: array<int, string>, assignee: ?string, project: ?string, label: ?string, type: ?string, group: ?string, sort: string, direction: string, custom_fields: array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'view' => $this->view,
            'status' => $this->status,
            'search' => $this->search,
            'priority' => $this->priorities,
            'assignee' => $this->assignee,
            'project' => $this->project,
            'label' => $this->label,
            'type' => $this->type,
            'group' => $this->group,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'custom_fields' => $this->customFields,
        ];
    }

    /**
     * Narrows a ticket query to what the toolbar's search and filters pick.
     * Shared by the ticket views and the calendar.
     *
     * @param  Builder<TicketEloquentModel>  $query
     */
    public function narrow(Builder $query, string $userId): void
    {
        $query
            ->when($this->status === FindTicket::FILTER_OPEN, fn (Builder $query) => $query->whereIn(StatusId::getDatabaseTableColumnName(), TicketStatusCategoryEnum::Open->statusIds()))
            ->when($this->status === FindTicket::FILTER_CLOSED, fn (Builder $query) => $query->whereIn(StatusId::getDatabaseTableColumnName(), TicketStatusCategoryEnum::Closed->statusIds()))
            ->when($this->priorities !== [], fn (Builder $query) => $query->whereIn(PriorityId::getDatabaseTableColumnName(), $this->priorities))
            ->when($this->assignee, fn (Builder $query, string $assignee) => match ($assignee) {
                self::ASSIGNEE_ME => $query->where(AssigneeId::getDatabaseTableColumnName(), $userId),
                self::NONE => $query->whereNull(AssigneeId::getDatabaseTableColumnName()),
                default => $query->where(AssigneeId::getDatabaseTableColumnName(), $assignee),
            })
            ->when($this->project, fn (Builder $query, string $project) => $project === self::NONE
                ? $query->whereNull(ProjectId::getDatabaseTableColumnName())
                : $query->where(ProjectId::getDatabaseTableColumnName(), $project))
            ->when($this->label, fn (Builder $query, string $label) => $query->whereHas(
                TicketRelationship::Labels->value,
                fn (Builder $query) => $query->whereKey($label),
            ))
            ->when($this->type, fn (Builder $query, string $type) => $type === self::NONE
                ? $query->whereNull(TypeId::getDatabaseTableColumnName())
                : $query->where(TypeId::getDatabaseTableColumnName(), $type))
            ->when($this->group, fn (Builder $query, string $group) => $group === self::NONE
                ? $query->whereNull(GroupId::getDatabaseTableColumnName())
                : $query->where(GroupId::getDatabaseTableColumnName(), $group))
            ->when($this->search, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where(Title::getDatabaseTableColumnName(), 'like', "%{$search}%")
                ->orWhere(Number::getDatabaseTableColumnName(), ltrim($search, '#'))));

        foreach ($this->fields ?? [] as $field) {
            $value = $this->customFields[$field->getId()->toString()] ?? null;

            if ($value !== null) {
                CustomFieldQuery::filter($query, $field, $value);
            }
        }
    }

    /**
     * Orders a ticket query by the toolbar's sort.
     *
     * @param  Builder<TicketEloquentModel>  $query
     */
    public function sort(Builder $query): void
    {
        $isAscending = $this->direction === 'asc';
        $customField = CustomFieldQuery::sortField($this->sort, $this->fields ?? CustomFieldCollection::make());

        if ($customField->isNotNull()) {
            CustomFieldQuery::sort($query, $customField, $this->direction);
            $query->orderByDesc(Number::getDatabaseTableColumnName());

            return;
        }

        match ($this->sort) {
            // The first priority in the team's list is the most urgent, so "descending" runs up the list.
            'priority' => $query->orderBy(
                ChoiceEloquentModel::query()
                    ->withoutGlobalScopes()
                    ->select(ChoicePosition::getDatabaseTableColumnName())
                    ->whereColumn(ChoiceId::getDatabaseTableColumnNameWithTable(Table::Choices->value), PriorityId::getDatabaseTableColumnNameWithTable(Table::Tickets->value)),
                $isAscending ? 'desc' : 'asc',
            )->orderByDesc(Number::getDatabaseTableColumnName()),
            'status' => $query->orderBy(
                ChoiceEloquentModel::query()
                    ->withoutGlobalScopes()
                    ->select(ChoicePosition::getDatabaseTableColumnName())
                    ->whereColumn(ChoiceId::getDatabaseTableColumnNameWithTable(Table::Choices->value), StatusId::getDatabaseTableColumnNameWithTable(Table::Tickets->value)),
                $isAscending ? 'asc' : 'desc',
            )->orderByDesc(Number::getDatabaseTableColumnName()),
            'due_on' => $query->orderByRaw($isAscending ? 'due_on is null, due_on asc' : 'due_on is null, due_on desc')
                ->orderByDesc(Number::getDatabaseTableColumnName()),
            default => $query->orderBy(self::SORTS[$this->sort], $this->direction),
        };
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private static function idOrKeyword(mixed $value, array $keywords): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return in_array($value, $keywords, true) || Str::isUuid($value) ? $value : null;
    }
}
