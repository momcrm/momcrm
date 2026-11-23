<?php

declare(strict_types=1);

namespace App\Timesheet\FormRequests;

use App\Attendance\AttendanceTimezone;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use App\Timesheet\Actions\FindTimesheet;
use App\Timesheet\Actions\NotifyTimesheetReviewers;
use App\Timesheet\Actions\SubmitTimesheet;
use App\Timesheet\Properties\StartsOn;
use App\Timesheet\TimesheetAccess;
use App\Timesheet\TimesheetPeriods;
use App\Timesheet\TimesheetStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Sends the member's own timesheet for a period that has started, or one
 * that was sent back, to the owners and admins.
 */
final class SubmitTimesheetFormRequest extends FormRequest
{
    public Team $team;

    public function __construct()
    {
        parent::__construct();

        $this->team = Team::new();
    }

    /**
     * @throws CouldNotViewTeam
     */
    public function authorize(): Response
    {
        $this->team = TimesheetAccess::team($this);

        return Response::allow();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [StartsOn::getNameForValidation() => ['required', 'date_format:Y-m-d']];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $startsOn = $this->startsOn();
            $today = CarbonImmutable::now()->setTimezone(AttendanceTimezone::forUserId($this->userId()))->toDateString();
            $existing = FindTimesheet::forMemberStartingOn($this->team->getId()->toString(), $this->userId(), $startsOn);

            if (! TimesheetPeriods::forTeam($this->team)->startsOn($startsOn)) {
                $validator->errors()->add(StartsOn::getNameForValidation(), __('That isn’t the start of a pay period.'));
            } elseif ($startsOn > $today) {
                $validator->errors()->add(StartsOn::getNameForValidation(), __('This period hasn’t started yet.'));
            } elseif ($existing->isNotNull() && $existing->getStatus()->toEnum() !== TimesheetStatusEnum::Rejected) {
                $validator->errors()->add(StartsOn::getNameForValidation(), __('This timesheet has already been submitted.'));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $period = TimesheetPeriods::forTeam($this->team)->containing($this->startsOn());
        $timesheet = SubmitTimesheet::forPeriod($this->team->getId()->toString(), $this->userId(), $period['starts_on'], $period['ends_on']);

        NotifyTimesheetReviewers::ofSubmission($timesheet);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Timesheet submitted.')]);

        return back();
    }

    private function startsOn(): string
    {
        return $this->string(StartsOn::getNameForValidation())->toString();
    }

    private function userId(): string
    {
        return (string) $this->user()?->id;
    }
}
