<?php

declare(strict_types=1);

namespace App\Attachment\FormRequests;

use App\Attachment\Actions\CreateAttachment;
use App\Attachment\Attachment;
use App\Attachment\AttachmentValidationRules;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Record\Exceptions\CouldNotFindRecord;
use App\Record\Record;
use App\Record\RecordAccess;
use App\Record\RecordEloquentModel;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

/**
 * Attaches files to a custom object's record.
 */
final class StoreRecordAttachmentFormRequest extends FormRequest
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

        return Gate::authorize('create', Attachment::class);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return AttachmentValidationRules::rules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return AttachmentValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        /** @var array<int, UploadedFile> $files */
        $files = $this->file(AttachmentValidationRules::FILES, []);
        $record = RecordEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->record->getId()->toString());

        foreach ($files as $file) {
            CreateAttachment::for($file, $record, (string) $this->user()?->id);
        }

        return back();
    }
}
