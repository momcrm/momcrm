<?php

declare(strict_types=1);

namespace App\Folder\FormRequests;

use App\Attachment\Attachment;
use App\Folder\Actions\FindFolder;
use App\Folder\Actions\UpdateFolder;
use App\Folder\Exceptions\CouldNotFindFolder;
use App\Folder\Folder;
use App\Folder\FolderValidationRules;
use App\Folder\Properties\Name;
use App\Folder\Properties\ParentId;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

/**
 * Renames a folder or moves it under another of the customer's folders (or
 * to the top level). A folder can't go inside itself or one of its own.
 */
final class UpdateFolderFormRequest extends FormRequest
{
    public Folder $folder;

    public function __construct()
    {
        parent::__construct();

        $this->folder = Folder::new();
    }

    /**
     * @throws CouldNotFindFolder
     */
    public function authorize(): Response
    {
        $this->folder = FindFolder::byIdForTeam((string) $this->route('folder'), $this->user()?->current_team_id);

        if ($this->folder->isNull()) {
            throw CouldNotFindFolder::notFound();
        }

        return Gate::authorize('create', Attachment::class);
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => ['sometimes', ...Name::getValidationRulesForUpdate($this)],
            ParentId::getNameForValidation() => [
                'sometimes',
                ...ParentId::getValidationRulesForUpdate($this),
                FolderValidationRules::inCustomer($this->folder->getCustomerId()->toNullableString()),
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

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $parentId = $this->input(ParentId::getNameForValidation());

            if (is_string($parentId) && in_array($parentId, FindFolder::withDescendantIds($this->folder), true)) {
                $validator->errors()->add(ParentId::getNameForValidation(), __('A folder can\'t go inside itself.'));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        UpdateFolder::fromFolder($this->folder->setPropertyIfExists($this->validated()));

        return back();
    }
}
