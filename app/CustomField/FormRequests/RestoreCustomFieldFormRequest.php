<?php

declare(strict_types=1);

namespace App\CustomField\FormRequests;

use App\CustomField\Actions\UpdateCustomField;
use App\CustomField\CustomField;
use App\CustomField\CustomFieldAccess;
use App\CustomField\Exceptions\CouldNotFindCustomField;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Brings an archived field back, with the values records still hold for it.
 */
final class RestoreCustomFieldFormRequest extends FormRequest
{
    public CustomField $field;

    public function __construct()
    {
        parent::__construct();

        $this->field = CustomField::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindCustomField
     */
    public function authorize(): Response
    {
        $this->field = CustomFieldAccess::field($this);

        if (! $this->field->isArchived()) {
            throw CouldNotFindCustomField::notFound();
        }

        return CustomFieldAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        UpdateCustomField::fromCustomField($this->field->setArchivedAt(null));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name restored.', ['name' => $this->field->getName()->toString()])]);

        return back();
    }
}
