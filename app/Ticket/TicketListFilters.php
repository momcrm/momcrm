<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Ticket\Actions\FindTicket;
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
        'updated_at' => 'updated_at',
    ];

    /**
     * @param  array<int, string>  $priorities
     * @param  'asc'|'desc'  $direction
     */
    public function __construct(
        public string $view = self::VIEW_LIST,
        public string $status = FindTicket::FILTER_OPEN,
        public ?string $search = null,
        public array $priorities = [],
        public ?string $assignee = null,
        public ?string $project = null,
        public ?string $label = null,
        public string $sort = 'number',
        public string $direction = 'desc',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $view = in_array($request->query('view'), [self::VIEW_BOARD, self::VIEW_TABLE], true) ? (string) $request->query('view') : self::VIEW_LIST;
        $status = (string) $request->query('status', '');
        $sort = (string) $request->query('sort', 'number');

        return new self(
            view: $view,
            status: in_array($status, [FindTicket::FILTER_OPEN, FindTicket::FILTER_CLOSED, FindTicket::FILTER_ALL], true)
                ? $status
                : ($view === self::VIEW_BOARD ? FindTicket::FILTER_ALL : FindTicket::FILTER_OPEN),
            search: $request->string('search')->trim()->toString() ?: null,
            priorities: collect(explode(',', (string) $request->query('priority', '')))
                ->filter(fn (string $priority): bool => Str::isUuid($priority))
                ->values()
                ->all(),
            assignee: self::idOrKeyword($request->query('assignee'), [self::ASSIGNEE_ME, self::NONE]),
            project: self::idOrKeyword($request->query('project'), [self::NONE]),
            label: self::idOrKeyword($request->query('label'), []),
            sort: array_key_exists($sort, self::SORTS) ? $sort : 'number',
            direction: $request->query('direction') === 'asc' ? 'asc' : 'desc',
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
            sort: $this->sort,
            direction: $this->direction,
        );
    }

    /**
     * @return array{view: string, status: string, search: ?string, priority: array<int, string>, assignee: ?string, project: ?string, label: ?string, sort: string, direction: string}
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
            'sort' => $this->sort,
            'direction' => $this->direction,
        ];
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
