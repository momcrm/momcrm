<?php

declare(strict_types=1);

namespace App\CustomField\FormRequests;

use App\CustomField\Actions\ReorderCustomFields;
use App\CustomField\CustomFieldAccess;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\Properties\Target;
use App\Shared\Table;
use App\Shared\Types;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

/**
 * Puts one kind of record's custom fields in a new order.
 */
final class ReorderCustomFieldsFormRequest extends FormRequest
{
    public const string IDS = 'ids';

    /**
     * @throws CouldNotViewTeam
     */
    public function authorize(): Response
    {
        return CustomFieldAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Target::getNameForValidation() => ['required', Rule::enum(CustomFieldTargetEnum::class)],
            self::IDS => ['required', 'array', 'min:1'],
            self::IDS.'.*' => [
                'uuid',
                'distinct',
                Rule::exists(Table::CustomFields->value, 'id')
                    ->where('team_id', $this->user()?->current_team_id)
                    ->where(Target::getDatabaseTableColumnName(), $this->string(Target::getNameForValidation())->toString()),
            ],
        ];
    }

    public function process(): RedirectResponse
    {
        ReorderCustomFields::forTeam(
            (string) $this->user()?->current_team_id,
            CustomFieldTargetEnum::from($this->string(Target::getNameForValidation())->toString()),
            Types::stringList($this->validated(self::IDS)),
        );

        return back();
    }
}
