<?php

declare(strict_types=1);

namespace App\Choice\FormRequests;

use App\Choice\Actions\CreateChoice;
use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceAccess;
use App\Choice\ChoiceListEnum;
use App\Choice\Properties\Category;
use App\Choice\Properties\Color;
use App\Choice\Properties\Icon;
use App\Choice\Properties\ListName;
use App\Choice\Properties\Name;
use App\Shared\Table;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Adds a choice to the bottom of one of the team's lists, such as a new
 * ticket status in the open or closed category.
 */
final class StoreChoiceFormRequest extends FormRequest
{
    /**
     * @throws CouldNotViewTeam
     */
    public function authorize(): Response
    {
        return ChoiceAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $list = ChoiceListEnum::tryFrom($this->string(ListName::getNameForValidation())->toString());

        return [
            ListName::getNameForValidation() => ['required', Rule::enum(ChoiceListEnum::class)],
            Name::getNameForValidation() => [
                ...Name::getValidationRulesForCreate($this),
                Rule::unique(Table::Choices->value, 'name')
                    ->where('team_id', $this->user()?->current_team_id)
                    ->where('list', $list?->value),
            ],
            Color::getNameForValidation() => Color::getValidationRulesForCreate($this),
            Icon::getNameForValidation() => ['nullable', Rule::in($list?->icons() ?? [])],
            Category::getNameForValidation() => $list?->categories() === []
                ? ['prohibited']
                : ['required', Rule::in($list?->categories() ?? [])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Name::getNameForValidation().'.unique' => __('That name is already in this list.'),
        ];
    }

    public function process(): RedirectResponse
    {
        $teamId = (string) $this->user()?->current_team_id;
        $list = ChoiceListEnum::from($this->string(ListName::getNameForValidation())->toString());
        $lastPosition = FindChoice::listForTeam($teamId, $list, withArchived: true)
            ->map(fn (Choice $choice): int => $choice->getPosition()->toInteger())
            ->max();

        $choice = CreateChoice::fromChoice(
            Choice::new()
                ->setTeamId($teamId)
                ->setListName($list)
                ->setName($this->string(Name::getNameForValidation())->trim()->toString())
                ->setColor($this->string(Color::getNameForValidation())->toString())
                ->setIcon($this->input(Icon::getNameForValidation()))
                ->setCategory($this->input(Category::getNameForValidation()))
                ->setPosition(is_int($lastPosition) ? $lastPosition + 1 : 0)
                ->setIsDefault(false),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $choice->getName()->toString()])]);

        return back();
    }
}
