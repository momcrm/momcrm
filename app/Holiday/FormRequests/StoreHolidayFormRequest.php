<?php

declare(strict_types=1);

namespace App\Holiday\FormRequests;

use App\Attendance\AttendanceAccess;
use App\Holiday\Actions\CreateHoliday;
use App\Holiday\Holiday;
use App\Holiday\Properties\Date;
use App\Holiday\Properties\Name;
use App\Holiday\Properties\TeamId;
use App\Shared\Table;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Adds a day off for the whole team. Owners and admins only.
 */
final class StoreHolidayFormRequest extends FormRequest
{
    /**
     * @throws CouldNotViewTeam
     */
    public function authorize(): Response
    {
        return AttendanceAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Date::getNameForValidation() => ['required', 'date_format:Y-m-d', Rule::unique(Table::Holidays->value, Date::getDatabaseTableColumnName())
                ->where(TeamId::getDatabaseTableColumnName(), (string) $this->user()?->current_team_id)],
            Name::getNameForValidation() => ['required', 'string', 'max:'.Name::MAX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [Date::getNameForValidation().'.unique' => __('There’s already a holiday on this day.')];
    }

    public function process(): RedirectResponse
    {
        $holiday = CreateHoliday::fromHoliday(Holiday::new()
            ->setTeamId(AttendanceAccess::team($this)->getId())
            ->setDate($this->string(Date::getNameForValidation())->toString())
            ->setName($this->string(Name::getNameForValidation())->trim()->toString()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $holiday->getName()->toString()])]);

        return back();
    }
}
