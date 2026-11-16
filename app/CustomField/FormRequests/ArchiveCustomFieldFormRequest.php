<?php

declare(strict_types=1);

namespace App\CustomField\FormRequests;

use App\CustomField\Actions\UpdateCustomField;
use App\CustomField\CustomField;
use App\CustomField\CustomFieldAccess;
use App\CustomField\Exceptions\CouldNotFindCustomField;
use App\Team\Exceptions\CouldNotViewTeam;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Archives a field: it is no longer offered on records, but the values they
 * hold are kept and come back if it is restored.
 */
final class ArchiveCustomFieldFormRequest extends FormRequest
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

        if ($this->field->isArchived()) {
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
        UpdateCustomField::fromCustomField($this->field->setArchivedAt(CarbonImmutable::now()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name archived.', ['name' => $this->field->getName()->toString()])]);

        return back();
    }
}
