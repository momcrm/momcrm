<?php

declare(strict_types=1);

namespace App\Record\FormRequests;

use App\CustomObject\CustomObject;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Record\Actions\CreateRecord;
use App\Record\Properties\Name;
use App\Record\Record;
use App\Record\RecordAccess;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Adds a record to an object.
 */
final class StoreRecordFormRequest extends FormRequest
{
    public CustomObject $object;

    public function __construct()
    {
        parent::__construct();

        $this->object = CustomObject::new();
    }

    /**
     * @throws CouldNotFindCustomObject
     */
    public function authorize(): Response
    {
        $this->object = RecordAccess::object($this);

        return Gate::authorize('create', [Record::class, RecordAccess::blank($this->object)]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        $record = CreateRecord::fromRecord(
            RecordAccess::blank($this->object)
                ->setName($this->string(Name::getNameForValidation())->trim()->toString())
                ->setCreatedById((string) $this->user()?->id),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $record->getName()->toString()])]);

        return to_route('records.show', [$this->object->getId()->toString(), $record->getId()->toString()]);
    }
}
