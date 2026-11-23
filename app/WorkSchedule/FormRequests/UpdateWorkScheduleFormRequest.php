<?php

declare(strict_types=1);

namespace App\WorkSchedule\FormRequests;

use App\Attendance\AttendanceAccess;
use App\Team\Exceptions\CouldNotViewTeam;
use App\WorkSchedule\Actions\FindWorkSchedule;
use App\WorkSchedule\Actions\SaveWorkSchedule;
use App\WorkSchedule\Exceptions\CouldNotFindWorkSchedule;
use App\WorkSchedule\WorkSchedule;
use App\WorkSchedule\WorkScheduleDays;
use App\WorkSchedule\WorkScheduleValidationRules;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Changes a schedule's name, hours or grace. Past days are measured against
 * the new hours too. Owners and admins only.
 */
final class UpdateWorkScheduleFormRequest extends FormRequest
{
    public WorkSchedule $schedule;

    public function __construct()
    {
        parent::__construct();

        $this->schedule = WorkSchedule::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindWorkSchedule
     */
    public function authorize(): Response
    {
        $this->schedule = FindWorkSchedule::byIdForTeam((string) $this->route('workSchedule'), AttendanceAccess::team($this)->getId()->toString());

        if ($this->schedule->isNull()) {
            throw CouldNotFindWorkSchedule::notFound();
        }

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
        $schedule = SaveWorkSchedule::fromWorkSchedule(WorkScheduleValidationRules::fill($this->schedule, $this));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $schedule->getName()->toString()])]);

        return back();
    }
}
