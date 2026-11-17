<?php

declare(strict_types=1);

namespace App\CustomObject\FormRequests;

use App\CustomObject\Actions\CreateCustomObject;
use App\CustomObject\Actions\FindCustomObject;
use App\CustomObject\CustomObject;
use App\CustomObject\CustomObjectAccess;
use App\CustomObject\CustomObjectValidationRules;
use App\CustomObject\Properties\Hue;
use App\Shared\HuePalette;
use App\Shared\Types;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Adds an object to the bottom of the team's list.
 */
final class StoreCustomObjectFormRequest extends FormRequest
{
    /**
     * @throws CouldNotViewTeam
     */
    public function authorize(): Response
    {
        return CustomObjectAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return CustomObjectValidationRules::for($this, (string) $this->user()?->current_team_id);
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
        $teamId = (string) $this->user()?->current_team_id;
        $validated = Types::stringKeyed($this->validated());
        $lastPosition = FindCustomObject::listForTeam($teamId, withArchived: true)
            ->map(fn (CustomObject $object): int => $object->getPosition()->toInteger())
            ->max();

        $object = CustomObject::new()
            ->setPropertyIfExists($validated)
            ->setTeamId($teamId)
            ->setPosition(is_int($lastPosition) ? $lastPosition + 1 : 0);

        if (! array_key_exists(Hue::getNameForValidation(), $validated)) {
            $object->setHue(HuePalette::forName('custom_object', $object->getNamePlural()->toString()));
        }

        $object = CreateCustomObject::fromCustomObject($object);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $object->getNamePlural()->toString()])]);

        return back();
    }
}
