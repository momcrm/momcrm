<?php

declare(strict_types=1);

namespace App\Shift\FormRequests;

use App\Shift\Actions\DeleteShift;
use App\Shift\Actions\FindShift;
use App\Shift\Exceptions\CouldNotFindShift;
use App\Shift\Shift;
use App\Team\Actions\FindTeam;
use App\Team\Team;
use App\Timesheet\TimesheetLock;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Removes a shift recorded by mistake, unless its day is in an approved
 * timesheet. Owners and admins only.
 */
final class DeleteShiftFormRequest extends FormRequest
{
    public Shift $shift;

    public Team $team;

    public function __construct()
    {
        parent::__construct();

        $this->shift = Shift::new();
        $this->team = Team::new();
    }

    /**
     * @throws CouldNotFindShift
     */
    public function authorize(): Response
    {
        $this->team = FindTeam::byIdForMember($this->user()?->current_team_id, (string) $this->user()?->id);
        $this->shift = FindShift::byIdForTeam((string) $this->route('shift'), $this->team->getId()->toNullableString());

        if ($this->shift->isNull()) {
            throw CouldNotFindShift::notFound();
        }

        return Gate::authorize('manageAttendance', $this->team);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (TimesheetLock::isLocked($this->shift->getTeamId()->toString(), $this->shift->getUserId()->toString(), $this->shift->getWorkDate()->toDateString(), byMember: false)) {
                $validator->errors()->add('shift', __('This day is in an approved timesheet, so its time can’t change.'));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        DeleteShift::fromShift($this->shift);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shift removed.')]);

        return back();
    }
}
