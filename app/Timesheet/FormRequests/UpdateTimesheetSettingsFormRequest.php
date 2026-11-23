<?php

declare(strict_types=1);

namespace App\Timesheet\FormRequests;

use App\Attendance\AttendanceAccess;
use App\Team\Actions\UpdateTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Properties\TimesheetPeriod;
use App\Timesheet\TimesheetPeriodEnum;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Sets how long the team's pay periods are. Switching to fortnights counts
 * them from this week's Monday. Owners and admins only.
 */
final class UpdateTimesheetSettingsFormRequest extends FormRequest
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
        return [TimesheetPeriod::getNameForValidation() => ['required', Rule::enum(TimesheetPeriodEnum::class)]];
    }

    public function process(): RedirectResponse
    {
        $team = AttendanceAccess::team($this);
        $period = TimesheetPeriodEnum::from($this->string(TimesheetPeriod::getNameForValidation())->toString());

        if ($period !== $team->getTimesheetPeriod()->toEnum()) {
            UpdateTeam::fromTeam($team
                ->setTimesheetPeriod($period)
                ->setTimesheetStartsOn($period === TimesheetPeriodEnum::Fortnightly ? CarbonImmutable::today()->startOfWeek(CarbonImmutable::MONDAY) : null));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pay periods saved.')]);

        return back();
    }
}
