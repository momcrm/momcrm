<?php

declare(strict_types=1);

namespace App\Timesheet\FormRequests;

use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use App\Timesheet\Actions\FindTimesheet;
use App\Timesheet\Actions\NotifyTimesheetReviewers;
use App\Timesheet\Actions\ReviewTimesheet;
use App\Timesheet\Exceptions\CouldNotFindTimesheet;
use App\Timesheet\Properties\ReviewNote;
use App\Timesheet\Timesheet;
use App\Timesheet\TimesheetAccess;
use App\Timesheet\TimesheetStatusEnum;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Sends a submitted timesheet back with a note, so the member can fix it,
 * and tells the member. Owners and admins only.
 */
final class RejectTimesheetFormRequest extends FormRequest
{
    public Timesheet $timesheet;

    public Team $team;

    public function __construct()
    {
        parent::__construct();

        $this->timesheet = Timesheet::new();
        $this->team = Team::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindTimesheet
     */
    public function authorize(): Response
    {
        $this->team = TimesheetAccess::team($this);
        $this->timesheet = FindTimesheet::byIdForTeam((string) $this->route('timesheet'), $this->team->getId()->toString());

        if ($this->timesheet->isNull()) {
            throw CouldNotFindTimesheet::notFound();
        }

        return Gate::authorize('manageAttendance', $this->team);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [ReviewNote::getNameForValidation() => ['nullable', 'string', 'max:'.ReviewNote::MAX]];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->timesheet->getStatus()->toEnum() !== TimesheetStatusEnum::Submitted) {
                $validator->errors()->add('timesheet', __('This timesheet isn’t waiting for approval.'));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $reviewerId = (string) $this->user()?->id;
        $note = $this->filled(ReviewNote::getNameForValidation()) ? $this->string(ReviewNote::getNameForValidation())->trim()->toString() : null;
        $timesheet = ReviewTimesheet::decide($this->timesheet, TimesheetStatusEnum::Rejected, $reviewerId, $note);

        NotifyTimesheetReviewers::ofDecision($timesheet, $reviewerId);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Timesheet sent back.')]);

        return back();
    }
}
