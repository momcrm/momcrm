<?php

declare(strict_types=1);

namespace App\Portal\FormRequests;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\Attachment\Actions\CreateAttachment;
use App\Attachment\AttachmentValidationRules;
use App\Portal\Actions\FindPortalTickets;
use App\Portal\PortalAccess;
use App\Portal\PortalContact;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

/**
 * Adds files to one of the customer's tickets from the portal, shared with
 * the customer.
 */
final class StorePortalTicketFilesFormRequest extends FormRequest
{
    public Ticket $ticket;

    public function __construct()
    {
        parent::__construct();

        $this->ticket = Ticket::new();
    }

    /**
     * @throws AuthorizationException
     */
    public function authorize(): bool
    {
        $this->ticket = FindPortalTickets::visibleToContact((string) $this->route('ticket'));

        PortalAccess::authorize(AccessResourceEnum::Attachment, AccessActionEnum::Create);

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
        $contact = PortalContact::current();
        $model = TicketEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->ticket->getId()->toString());
        $files = $this->file(AttachmentValidationRules::FILES, []);

        foreach ($files instanceof UploadedFile ? [$files] : $files as $file) {
            CreateAttachment::fromPortal($file, $model, $contact->getId()->toString(), $contact->getFullName());
        }

        return back();
    }
}
