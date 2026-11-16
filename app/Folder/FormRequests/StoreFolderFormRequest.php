<?php

declare(strict_types=1);

namespace App\Folder\FormRequests;

use App\Attachment\Attachment;
use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Folder\Actions\CreateFolder;
use App\Folder\Folder;
use App\Folder\FolderValidationRules;
use App\Folder\Properties\Name;
use App\Folder\Properties\ParentId;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Adds a folder to a customer's files, at the top level or inside another.
 * Anyone who may add files may sort them into folders.
 */
final class StoreFolderFormRequest extends FormRequest
{
    public Customer $customer;

    public function __construct()
    {
        parent::__construct();

        $this->customer = Customer::new();
    }

    /**
     * @throws CouldNotFindCustomer
     */
    public function authorize(): Response
    {
        $this->customer = FindCustomer::byIdForTeam((string) $this->route('customer'), $this->user()?->current_team_id);

        if ($this->customer->isNull()) {
            throw CouldNotFindCustomer::notFound();
        }

        return Gate::authorize('create', Attachment::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($this),
            ParentId::getNameForValidation() => [
                ...ParentId::getValidationRulesForCreate($this),
                FolderValidationRules::inCustomer($this->customer->getId()->toNullableString()),
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
        CreateFolder::fromFolder(Folder::new()
            ->setPropertyIfExists($this->validated())
            ->setTeamId($this->customer->getTeamId())
            ->setCustomerId($this->customer->getId()));

        return back();
    }
}
