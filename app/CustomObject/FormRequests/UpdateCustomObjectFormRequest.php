<?php

declare(strict_types=1);

namespace App\CustomObject\FormRequests;

use App\CustomObject\Actions\UpdateCustomObject;
use App\CustomObject\CustomObject;
use App\CustomObject\CustomObjectAccess;
use App\CustomObject\CustomObjectValidationRules;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Shared\Types;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Renames an object or changes its description, icon or colour. Fields left
 * out keep their value.
 */
final class UpdateCustomObjectFormRequest extends FormRequest
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

        return CustomObjectAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return CustomObjectValidationRules::for($this, $this->object->getTeamId()->toString(), $this->object);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return CustomObjectValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        UpdateCustomObject::fromCustomObject($this->object->setPropertyIfExists(Types::stringKeyed($this->validated())));

        return back();
    }
}
