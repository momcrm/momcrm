<?php

declare(strict_types=1);

namespace App\Comment\FormRequests;

use App\Comment\Actions\CreateRecordComment;
use App\Comment\Comment;
use App\Comment\Properties\Body;
use App\Comment\Properties\Mentions;
use App\CustomObject\CustomObject;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Record\Exceptions\CouldNotFindRecord;
use App\Record\Record;
use App\Record\RecordAccess;
use App\Shared\Table;
use App\Shared\Types;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Adds a comment to a custom object's record. Whoever may comment on
 * tickets may comment here: comments share one set of access rules.
 */
final class StoreRecordCommentFormRequest extends FormRequest
{
    public CustomObject $object;

    public Record $record;

    public function __construct()
    {
        parent::__construct();

        $this->object = CustomObject::new();
        $this->record = Record::new();
    }

    /**
     * @throws CouldNotFindCustomObject
     * @throws CouldNotFindRecord
     */
    public function authorize(): Response
    {
        $this->object = RecordAccess::object($this);
        $this->record = RecordAccess::record($this, $this->object);

        return Gate::authorize('create', Comment::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Body::getNameForValidation() => Body::getValidationRulesForCreate($this),
            Mentions::getNameForValidation() => ['sometimes', 'array', 'max:20'],
            Mentions::getNameForValidation().'.*' => ['uuid', Rule::exists(Table::TeamUser->value, 'user_id')->where('team_id', $this->user()?->current_team_id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Body::getNameForValidation().'.required' => __('Write a comment first.'),
            Mentions::getNameForValidation().'.*.exists' => __('You can only mention people in your team.'),
        ];
    }

    public function process(): RedirectResponse
    {
        $mentions = array_values(array_unique(Types::stringList($this->validated()[Mentions::getNameForValidation()] ?? [])));

        CreateRecordComment::fromComment(
            Comment::new()
                ->setTeamId($this->record->getTeamId())
                ->setRecordId($this->record->getId())
                ->setUserId((string) $this->user()?->id)
                ->setBody(trim($this->string(Body::getNameForValidation())->toString()))
                ->setMentions($mentions === [] ? null : $mentions),
            $this->object,
        );

        return back();
    }
}
