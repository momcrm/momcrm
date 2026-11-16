<?php

declare(strict_types=1);

namespace App\Attachment\FormRequests;

use App\Attachment\Actions\CreateAttachment;
use App\Attachment\Attachment;
use App\Attachment\AttachmentValidationRules;
use App\Attachment\Properties\FolderId;
use App\Customer\Actions\FindCustomer;
use App\Customer\CustomerEloquentModel;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Folder\FolderValidationRules;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Uploads files to a customer, into one of its folders or the top level.
 */
final class StoreCustomerAttachmentFormRequest extends FormRequest
{
    public ?CustomerEloquentModel $customer = null;

    /**
     * @throws CouldNotFindCustomer
     */
    public function authorize(): Response
    {
        $customer = FindCustomer::byIdForTeam((string) $this->route('customer'), $this->user()?->current_team_id);

        if ($customer->isNull()) {
            throw CouldNotFindCustomer::notFound();
        }

        $this->customer = CustomerEloquentModel::query()->withoutGlobalScopes()->find($customer->getId()->toString());

        return Gate::authorize('create', Attachment::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...AttachmentValidationRules::rules(),
            FolderId::getNameForValidation() => [
                ...FolderId::getValidationRulesForCreate($this),
                FolderValidationRules::inCustomer($this->customer?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [...AttachmentValidationRules::messages(), ...FolderValidationRules::messages()];
    }

    public function process(): RedirectResponse
    {
        /** @var array<int, UploadedFile> $files */
        $files = $this->file(AttachmentValidationRules::FILES, []);
        $folderId = $this->string(FolderId::getNameForValidation())->toString() ?: null;

        foreach ($files as $file) {
            CreateAttachment::for($file, $this->customer ?? new CustomerEloquentModel, (string) $this->user()?->id, $folderId);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice('{1} 1 file uploaded.|[2,*] :count files uploaded.', count($files))]);

        return back();
    }
}
