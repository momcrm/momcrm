<?php

declare(strict_types=1);

namespace App\Choice\FormRequests;

use App\Choice\Actions\DeleteChoice;
use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceAccess;
use App\Choice\Exceptions\CouldNotFindChoice;
use App\Shared\Table;
use App\Team\Exceptions\CouldNotViewTeam;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Deletes a choice. Records that use it move to a replacement from the same
 * list and category, so no ticket is left without a status or quietly
 * changes from open to closed. The default, and the last choice in a list or
 * category, can't be deleted.
 */
final class DeleteChoiceFormRequest extends FormRequest
{
    public const string REPLACEMENT_ID = 'replacement_id';

    public Choice $choice;

    public function __construct()
    {
        parent::__construct();

        $this->choice = Choice::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindChoice
     */
    public function authorize(): Response
    {
        $team = ChoiceAccess::team($this);
        $this->choice = FindChoice::byIdForTeam((string) $this->route('choice'), $team->getId()->toString());

        if ($this->choice->isNull()) {
            throw CouldNotFindChoice::notFound();
        }

        return ChoiceAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            self::REPLACEMENT_ID => [
                'nullable',
                'uuid',
                Rule::notIn([$this->choice->getId()->toString()]),
                Rule::exists(Table::Choices->value, 'id')
                    ->where('team_id', $this->choice->getTeamId()->toString())
                    ->where('list', $this->choice->getListName()->toEnum()->value)
                    ->where('category', $this->choice->getCategory()->toNullableString())
                    ->whereNull('archived_at'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            self::REPLACEMENT_ID.'.exists' => __('Choose another choice of the same kind to move them to.'),
            self::REPLACEMENT_ID.'.not_in' => __('Choose another choice of the same kind to move them to.'),
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $teamId = $this->choice->getTeamId()->toString();
            $list = $this->choice->getListName()->toEnum();
            $category = $this->choice->getCategory()->toNullableString();
            $siblings = FindChoice::listForTeam($teamId, $list)
                ->filter(fn (Choice $choice): bool => $choice->getCategory()->toNullableString() === $category);
            $usage = FindChoice::usageCountsForTeam($teamId, $list)[$this->choice->getId()->toString()] ?? 0;

            if ($this->choice->getIsDefault()->toBoolean()) {
                $validator->errors()->add('choice', __('Make another choice the default before deleting :name.', ['name' => $this->choice->getName()->toString()]));
            } elseif ($siblings->count() <= 1) {
                $validator->errors()->add('choice', __(':name is the last of its kind, so it has to stay.', ['name' => $this->choice->getName()->toString()]));
            } elseif ($usage > 0 && $this->input(self::REPLACEMENT_ID) === null) {
                $validator->errors()->add(self::REPLACEMENT_ID, trans_choice('Choose where to move the :count record that uses it.|Choose where to move the :count records that use it.', $usage, ['count' => $usage]));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $replacementId = $this->input(self::REPLACEMENT_ID);

        DeleteChoice::fromChoice($this->choice, is_string($replacementId) ? $replacementId : null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $this->choice->getName()->toString()])]);

        return back();
    }
}
