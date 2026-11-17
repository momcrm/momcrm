<?php

declare(strict_types=1);

namespace App\CustomObject\FormRequests;

use App\CustomObject\Actions\DeleteCustomObject;
use App\CustomObject\CustomObject;
use App\CustomObject\CustomObjectAccess;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Deletes an archived object for good, with everything in it. Archiving
 * first keeps an object from being lost in one click.
 */
final class DeleteCustomObjectFormRequest extends FormRequest
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

        if (! $this->object->isArchived()) {
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
        DeleteCustomObject::fromCustomObject($this->object);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $this->object->getNamePlural()->toString()])]);

        return back();
    }
}
