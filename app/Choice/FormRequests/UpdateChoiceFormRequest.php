<?php

declare(strict_types=1);

namespace App\Choice\FormRequests;

use App\Choice\Actions\FindChoice;
use App\Choice\Actions\MakeChoiceDefault;
use App\Choice\Actions\UpdateChoice;
use App\Choice\Choice;
use App\Choice\ChoiceAccess;
use App\Choice\Exceptions\CouldNotFindChoice;
use App\Choice\Properties\Color;
use App\Choice\Properties\Icon;
use App\Choice\Properties\IsDefault;
use App\Choice\Properties\Name;
use App\Shared\Table;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

/**
 * Renames or recolours a choice, changes its icon, or makes it the default.
 * Its list and category stay as they are, so a ticket never changes from
 * open to closed behind its back.
 */
final class UpdateChoiceFormRequest extends FormRequest
{
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
            Name::getNameForValidation() => [
                'sometimes',
                ...Name::getValidationRulesForCreate($this),
                Rule::unique(Table::Choices->value, 'name')
                    ->where('team_id', $this->choice->getTeamId()->toString())
                    ->where('list', $this->choice->getListName()->toEnum()->value)
                    ->ignore($this->choice->getId()->toString()),
            ],
            Color::getNameForValidation() => ['sometimes', ...Color::getValidationRulesForCreate($this)],
            Icon::getNameForValidation() => ['sometimes', 'nullable', Rule::in($this->choice->getListName()->toEnum()->icons())],
            IsDefault::getNameForValidation() => ['sometimes', 'accepted', ...($this->choice->isArchived() || $this->choice->getListName()->toEnum()->isOptional() ? ['prohibited'] : [])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Name::getNameForValidation().'.unique' => __('That name is already in this list, or in its archive.'),
            IsDefault::getNameForValidation().'.accepted' => __('Make another choice the default instead.'),
            IsDefault::getNameForValidation().'.prohibited' => $this->choice->getListName()->toEnum()->isOptional()
                ? __('This list has no default.')
                : __('Restore :name before making it the default.', ['name' => $this->choice->getName()->toString()]),
        ];
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();

        if (array_key_exists(Name::getNameForValidation(), $validated)) {
            $this->choice->setName($this->string(Name::getNameForValidation())->trim()->toString());
        }

        if (array_key_exists(Color::getNameForValidation(), $validated)) {
            $this->choice->setColor($validated[Color::getNameForValidation()]);
        }

        if (array_key_exists(Icon::getNameForValidation(), $validated)) {
            $this->choice->setIcon($validated[Icon::getNameForValidation()]);
        }

        $choice = UpdateChoice::fromChoice($this->choice);

        if (array_key_exists(IsDefault::getNameForValidation(), $validated)) {
            MakeChoiceDefault::fromChoice($choice);
        }

        return back();
    }
}
