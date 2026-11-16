<?php

declare(strict_types=1);

namespace App\Attachment\FormRequests;

use App\Attachment\Actions\CreateAttachment;
use App\Attachment\Attachment;
use App\Attachment\AttachmentValidationRules;
use App\Contract\Actions\FindContract;
use App\Contract\ContractEloquentModel;
use App\Contract\Exceptions\CouldNotFindContract;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

/**
 * Adds documents, such as the signed copy, to a contract.
 */
final class StoreContractAttachmentFormRequest extends FormRequest
{
    public ?ContractEloquentModel $contract = null;

    /**
     * @throws CouldNotFindContract
     */
    public function authorize(): Response
    {
        $contract = FindContract::byIdForTeam((string) $this->route('contract'), $this->user()?->current_team_id);

        if ($contract->isNull()) {
            throw CouldNotFindContract::notFound();
        }

        $this->contract = ContractEloquentModel::query()->withoutGlobalScopes()->find($contract->getId()->toString());

        return Gate::authorize('create', Attachment::class);
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
            CreateAttachment::for($file, $this->contract ?? new ContractEloquentModel, (string) $this->user()?->id);
        }

        return back();
    }
}
