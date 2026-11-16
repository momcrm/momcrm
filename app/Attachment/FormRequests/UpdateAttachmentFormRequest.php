<?php

declare(strict_types=1);

namespace App\Attachment\FormRequests;

use App\Attachment\Actions\FindAttachment;
use App\Attachment\Actions\UpdateAttachment;
use App\Attachment\Attachment;
use App\Attachment\Exceptions\CouldNotFindAttachment;
use App\Attachment\Properties\Description;
use App\Attachment\Properties\FolderId;
use App\Folder\FolderValidationRules;
use App\Shared\MorphMapKeyEnum;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Changes a file's description, or moves a customer's file into another of
 * its folders.
 */
final class UpdateAttachmentFormRequest extends FormRequest
{
    public Attachment $attachment;

    public function __construct()
    {
        parent::__construct();

        $this->attachment = Attachment::new();
    }

    /**
     * @throws CouldNotFindAttachment
     */
    public function authorize(): Response
    {
        $this->attachment = FindAttachment::visibleByIdForTeam((string) $this->route('attachment'), $this->user()?->current_team_id, (string) $this->user()?->id);

        if ($this->attachment->isNull()) {
            throw CouldNotFindAttachment::notFound();
        }

        return Gate::authorize('update', $this->attachment);
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    public function rules(): array
    {
        $customerId = $this->attachment->getAttachableType()->toString() === MorphMapKeyEnum::Customer->value
            ? $this->attachment->getAttachableId()->toNullableString()
            : null;

        return [
            Description::getNameForValidation() => ['sometimes', ...Description::getValidationRulesForUpdate($this)],
            FolderId::getNameForValidation() => [
                'sometimes',
                ...FolderId::getValidationRulesForUpdate($this),
                FolderValidationRules::inCustomer($customerId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return FolderValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        UpdateAttachment::fromAttachment($this->attachment->setPropertyIfExists($this->validated()));

        return back();
    }
}
