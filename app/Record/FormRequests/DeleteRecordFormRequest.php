<?php

declare(strict_types=1);

namespace App\Record\FormRequests;

use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Record\Actions\DeleteRecord;
use App\Record\Exceptions\CouldNotFindRecord;
use App\Record\Record;
use App\Record\RecordAccess;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Deletes a record for good and returns to its object's list.
 */
final class DeleteRecordFormRequest extends FormRequest
{
    public Record $record;

    public function __construct()
    {
        parent::__construct();

        $this->record = Record::new();
    }

    /**
     * @throws CouldNotFindCustomObject
     * @throws CouldNotFindRecord
     */
    public function authorize(): Response
    {
        $this->record = RecordAccess::record($this, RecordAccess::object($this));

        return Gate::authorize('delete', $this->record);
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
        DeleteRecord::fromRecord($this->record);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $this->record->getName()->toString()])]);

        return to_route('records.index', $this->record->getObjectId()->toString());
    }
}
