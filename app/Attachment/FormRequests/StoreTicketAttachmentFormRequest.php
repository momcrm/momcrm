<?php

declare(strict_types=1);

namespace App\Attachment\FormRequests;

use App\Attachment\Actions\CreateAttachment;
use App\Attachment\AttachmentValidationRules;
use App\Ticket\Exceptions\CouldNotFindTicket;
use App\Ticket\Properties\TeamId;
use App\Ticket\TicketEloquentModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

final class StoreTicketAttachmentFormRequest extends FormRequest
{
    public ?TicketEloquentModel $ticket = null;

    /**
     * @throws CouldNotFindTicket
     */
    public function authorize(): bool
    {
        $this->ticket = TicketEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $this->user()?->current_team_id)
            ->find((string) $this->route('ticket'));

        if ($this->ticket === null) {
            throw CouldNotFindTicket::notFound();
        }

        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return AttachmentValidationRules::rules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return AttachmentValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        /** @var array<int, UploadedFile> $files */
        $files = $this->file(AttachmentValidationRules::FILES, []);

        foreach ($files as $file) {
            CreateAttachment::for($file, $this->ticket ?? new TicketEloquentModel, (string) $this->user()?->getAuthIdentifier());
        }

        return back();
    }
}
