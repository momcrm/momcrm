<?php

declare(strict_types=1);

namespace App\Ticket\FormRequests;

use App\Attachment\Actions\CreateAttachment;
use App\Attachment\Attachment;
use App\Attachment\AttachmentValidationRules;
use App\Choice\LabelTargetEnum;
use App\Customer\Actions\FindCustomer;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldValueRules;
use App\CustomField\Properties\CustomFieldValues;
use App\Project\Actions\FindProject;
use App\Shared\Types;
use App\Ticket\Actions\CreateTicket;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketValidationRules;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Opens a ticket from the full form, the new-ticket dialog or a board column's
 * quick add. With `quick` it stays on the page it came from. The dialog can
 * also send labels, subtask titles and files, which are added with it.
 */
final class StoreTicketFormRequest extends FormRequest
{
    public const string QUICK = 'quick';

    public const string LABEL_IDS = 'label_ids';

    public const string SUBTASKS = 'subtasks';

    private const string CUSTOM_FIELDS = 'custom_fields';

    private const array EXTRAS = [self::QUICK, self::CUSTOM_FIELDS, self::LABEL_IDS, self::SUBTASKS, AttachmentValidationRules::FILES];

    public function authorize(): Response
    {
        if ($this->hasFile(AttachmentValidationRules::FILES)) {
            Gate::authorize('create', Attachment::class);
        }

        return Gate::authorize('create', Ticket::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $teamId = (string) $this->user()?->current_team_id;
        $fileRules = AttachmentValidationRules::rules();

        return [
            ...TicketValidationRules::for($this, $teamId),
            ...TicketValidationRules::status($this, $teamId),
            ...CustomFieldValueRules::for($teamId, CustomFieldTargetEnum::Ticket),
            self::QUICK => ['sometimes', 'boolean'],
            self::LABEL_IDS => ['sometimes', 'array'],
            self::LABEL_IDS.'.*' => ['uuid', LabelTargetEnum::Ticket->rule($teamId)],
            self::SUBTASKS => ['sometimes', 'array', 'max:50'],
            self::SUBTASKS.'.*' => ['required', 'string', 'max:255'],
            AttachmentValidationRules::FILES => ['sometimes', ...array_values(array_diff($fileRules[AttachmentValidationRules::FILES], ['required', 'min:1']))],
            AttachmentValidationRules::FILES.'.*' => $fileRules[AttachmentValidationRules::FILES.'.*'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...TicketValidationRules::messages(),
            ...AttachmentValidationRules::messages(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return CustomFieldValueRules::attributes((string) $this->user()?->current_team_id, CustomFieldTargetEnum::Ticket);
    }

    public function process(): RedirectResponse
    {
        $teamId = (string) $this->user()?->current_team_id;
        $userId = (string) $this->user()?->id;
        $validated = $this->validated();

        $ticket = CreateTicket::fromTicket(
            Ticket::new()
                ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, self::EXTRAS)))
                ->setCustomFields(CustomFieldValueRules::merge($teamId, CustomFieldTargetEnum::Ticket, new CustomFieldValues(null), $validated[self::CUSTOM_FIELDS] ?? []))
                ->setTeamId($teamId),
            openedById: $userId,
        );

        $this->addExtras($ticket, $validated, $userId);

        $for = FindCustomer::byIdForTeam($ticket->getCustomerId()->toNullableString(), $teamId)->getName()->toNullableString()
            ?? FindProject::byIdForTeam($ticket->getProjectId()->toNullableString(), $teamId)->getName()->toNullableString();

        Inertia::flash('toast', ['type' => 'success', 'message' => $for === null
            ? __('Ticket #:number opened.', ['number' => $ticket->getNumber()->toInteger()])
            : __('Ticket #:number opened for :customer.', ['number' => $ticket->getNumber()->toInteger(), 'customer' => $for])]);

        return $this->boolean(self::QUICK)
            ? back()
            : to_route('tickets.show', $ticket->getId()->toString());
    }

    /**
     * Labels, subtasks and files sent along with the new ticket.
     *
     * @param  array<array-key, mixed>  $validated
     */
    private function addExtras(Ticket $ticket, array $validated, string $userId): void
    {
        $labelIds = Types::stringList($validated[self::LABEL_IDS] ?? []);
        $subtasks = Types::stringList($validated[self::SUBTASKS] ?? []);
        /** @var array<int, UploadedFile> $files */
        $files = $this->file(AttachmentValidationRules::FILES, []);

        if ($labelIds === [] && $subtasks === [] && $files === []) {
            return;
        }

        $model = TicketEloquentModel::query()->withoutGlobalScopes()->findOrFail($ticket->getId()->toString());

        if ($labelIds !== []) {
            $model->labels()->sync($labelIds);
        }

        foreach ($subtasks as $title) {
            CreateTicket::fromTicket(
                Ticket::new()
                    ->setTeamId($ticket->getTeamId())
                    ->setParentId($ticket->getId())
                    ->setCustomerId($ticket->getCustomerId())
                    ->setProjectId($ticket->getProjectId())
                    ->setTitle($title),
                openedById: $userId,
            );
        }

        foreach ($files as $file) {
            CreateAttachment::for($file, $model, $userId);
        }
    }
}
