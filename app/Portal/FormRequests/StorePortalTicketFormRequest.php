<?php

declare(strict_types=1);

namespace App\Portal\FormRequests;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\Attachment\Actions\CreateAttachment;
use App\Attachment\AttachmentValidationRules;
use App\Choice\ChoiceListEnum;
use App\Portal\Actions\NotifyStaffOfPortalTicket;
use App\Portal\PortalAccess;
use App\Portal\PortalContact;
use App\Ticket\Actions\CreateTicket;
use App\Ticket\Properties\Description;
use App\Ticket\Properties\PriorityId;
use App\Ticket\Properties\Title;
use App\Ticket\Properties\TypeId;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketValidationRules;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Raises a ticket for the contact's customer, with them as its contact and
 * requester, and any files they attached shared with the customer. Staff
 * watching the customer, or else the owners and admins, are told.
 */
final class StorePortalTicketFormRequest extends FormRequest
{
    /**
     * @throws AuthorizationException
     */
    public function authorize(): bool
    {
        PortalAccess::authorize(AccessResourceEnum::Ticket, AccessActionEnum::Create);

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $teamId = PortalContact::current()->getTeamId()->toString();

        return [
            Title::getNameForValidation() => Title::getValidationRulesForCreate($this),
            Description::getNameForValidation() => Description::getValidationRulesForCreate($this),
            TypeId::getNameForValidation() => [...TypeId::getValidationRulesForCreate($this), TicketValidationRules::choice($teamId, ChoiceListEnum::TicketType)],
            PriorityId::getNameForValidation() => [...PriorityId::getValidationRulesForCreate($this), TicketValidationRules::choice($teamId, ChoiceListEnum::TicketPriority)],
            AttachmentValidationRules::FILES => ['sometimes', 'array', 'max:10'],
            AttachmentValidationRules::FILES.'.*' => AttachmentValidationRules::rules()[AttachmentValidationRules::FILES.'.*'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Title::getNameForValidation().'.required' => __('Say what the ticket is about.'),
            ...AttachmentValidationRules::messages(),
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->hasFile(AttachmentValidationRules::FILES) && ! PortalAccess::allows(AccessResourceEnum::Attachment, AccessActionEnum::Create)) {
                $validator->errors()->add(AttachmentValidationRules::FILES, __("You can't add files."));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $contact = PortalContact::current();
        $name = $contact->getFullName();

        $ticket = DB::transaction(function () use ($contact, $name): Ticket {
            $ticket = CreateTicket::fromTicket(
                Ticket::new()
                    ->setTeamId($contact->getTeamId())
                    ->setCustomerId($contact->getCustomerId())
                    ->setContactId($contact->getId())
                    ->setRequesterName($name)
                    ->setRequesterEmail($contact->getEmail())
                    ->setTitle($this->string(Title::getNameForValidation())->toString())
                    ->setDescription($this->input(Description::getNameForValidation()))
                    ->setTypeId($this->input(TypeId::getNameForValidation()))
                    ->setPriorityId($this->input(PriorityId::getNameForValidation())),
                openedByName: $name,
            );

            $model = TicketEloquentModel::query()->withoutGlobalScopes()->findOrFail($ticket->getId()->toString());

            foreach ($this->uploadedFiles() as $file) {
                CreateAttachment::fromPortal($file, $model, $contact->getId()->toString(), $name);
            }

            NotifyStaffOfPortalTicket::send($model, $name);

            return $ticket;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ticket #:number raised.', ['number' => $ticket->getNumber()->toInteger()])]);

        return to_route('tickets.show', $ticket->getId()->toString());
    }

    /**
     * @return list<UploadedFile>
     */
    private function uploadedFiles(): array
    {
        $files = $this->file(AttachmentValidationRules::FILES, []);

        return array_values($files instanceof UploadedFile ? [$files] : $files);
    }
}
