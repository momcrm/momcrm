<?php

declare(strict_types=1);

namespace App\CustomObject\FormRequests;

use App\CustomObject\Actions\ReorderCustomObjects;
use App\CustomObject\CustomObjectAccess;
use App\Shared\Table;
use App\Shared\Types;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

/**
 * Puts the team's objects in a new order.
 */
final class ReorderCustomObjectsFormRequest extends FormRequest
{
    public const string IDS = 'ids';

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
        return [
            self::IDS => ['required', 'array', 'min:1'],
            self::IDS.'.*' => [
                'uuid',
                'distinct',
                Rule::exists(Table::CustomObjects->value, 'id')->where('team_id', $this->user()?->current_team_id),
            ],
        ];
    }

    public function process(): RedirectResponse
    {
        ReorderCustomObjects::forTeam((string) $this->user()?->current_team_id, Types::stringList($this->validated(self::IDS)));

        return back();
    }
}
