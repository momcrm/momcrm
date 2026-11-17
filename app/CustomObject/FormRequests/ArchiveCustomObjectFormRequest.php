<?php

declare(strict_types=1);

namespace App\CustomObject\FormRequests;

use App\CustomObject\Actions\UpdateCustomObject;
use App\CustomObject\CustomObject;
use App\CustomObject\CustomObjectAccess;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Team\Exceptions\CouldNotViewTeam;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Archives an object: it leaves the sidebar and no records can be added,
 * but its records are kept and come back if it is restored.
 */
final class ArchiveCustomObjectFormRequest extends FormRequest
{
    public CustomObject $object;

    public function __construct()
    {
        parent::__construct();

        $this->object = CustomObject::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindCustomObject
     */
    public function authorize(): Response
    {
        $this->object = CustomObjectAccess::object($this);

        if ($this->object->isArchived()) {
            throw CouldNotFindCustomObject::notFound();
        }

        return CustomObjectAccess::authorize($this);
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
        UpdateCustomObject::fromCustomObject($this->object->setArchivedAt(CarbonImmutable::now()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name archived.', ['name' => $this->object->getNamePlural()->toString()])]);

        return back();
    }
}
