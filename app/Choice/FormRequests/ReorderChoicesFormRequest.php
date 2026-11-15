<?php

declare(strict_types=1);

namespace App\Choice\FormRequests;

use App\Choice\Actions\ReorderChoices;
use App\Choice\ChoiceAccess;
use App\Choice\ChoiceListEnum;
use App\Choice\Properties\ListName;
use App\Shared\Table;
use App\Shared\Types;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

/**
 * Puts one of the team's lists in a new order.
 */
final class ReorderChoicesFormRequest extends FormRequest
{
    public const string IDS = 'ids';

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
        return [
            ListName::getNameForValidation() => ['required', Rule::enum(ChoiceListEnum::class)],
            self::IDS => ['required', 'array', 'min:1'],
            self::IDS.'.*' => [
                'uuid',
                'distinct',
                Rule::exists(Table::Choices->value, 'id')
                    ->where('team_id', $this->user()?->current_team_id)
                    ->where('list', $this->string(ListName::getNameForValidation())->toString()),
            ],
        ];
    }

    public function process(): RedirectResponse
    {
        ReorderChoices::forTeam(
            (string) $this->user()?->current_team_id,
            ChoiceListEnum::from($this->string(ListName::getNameForValidation())->toString()),
            Types::stringList($this->validated(self::IDS)),
        );

        return back();
    }
}
