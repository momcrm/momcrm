<?php

declare(strict_types=1);

namespace App\WorkSchedule\FormRequests;

use App\Attendance\AttendanceAccess;
use App\Team\Exceptions\CouldNotViewTeam;
use App\WorkSchedule\Actions\SaveWorkSchedule;
use App\WorkSchedule\WorkSchedule;
use App\WorkSchedule\WorkScheduleDays;
use App\WorkSchedule\WorkScheduleValidationRules;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Adds a schedule. Owners and admins only.
 */
final class StoreWorkScheduleFormRequest extends FormRequest
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
        return WorkScheduleValidationRules::rules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return WorkScheduleValidationRules::attributes();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return WorkScheduleValidationRules::messages();
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => WorkScheduleDays::check($validator, $this->input('days'))];
    }

    public function process(): RedirectResponse
    {
        $schedule = SaveWorkSchedule::fromWorkSchedule(WorkScheduleValidationRules::fill(
            WorkSchedule::new()->setTeamId(AttendanceAccess::team($this)->getId()),
            $this,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $schedule->getName()->toString()])]);

        return back();
    }
}
