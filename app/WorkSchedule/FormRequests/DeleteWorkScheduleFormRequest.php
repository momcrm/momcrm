<?php

declare(strict_types=1);

namespace App\WorkSchedule\FormRequests;

use App\Attendance\AttendanceAccess;
use App\Team\Exceptions\CouldNotViewTeam;
use App\WorkSchedule\Actions\DeleteWorkSchedule;
use App\WorkSchedule\Actions\FindWorkSchedule;
use App\WorkSchedule\Exceptions\CouldNotFindWorkSchedule;
use App\WorkSchedule\WorkSchedule;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Deletes a schedule; its members move to the default. The default itself
 * can only go once it is the last one, so members are never left without
 * hours while other schedules exist. Owners and admins only.
 */
final class DeleteWorkScheduleFormRequest extends FormRequest
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
        return [];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $others = FindWorkSchedule::listForTeam($this->schedule->getTeamId()->toString())->count() - 1;

            if ($this->schedule->getIsDefault()->toBoolean() && $others > 0) {
                $validator->errors()->add('schedule', __('Make another schedule the default before deleting this one.'));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        DeleteWorkSchedule::fromWorkSchedule($this->schedule);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $this->schedule->getName()->toString()])]);

        return back();
    }
}
