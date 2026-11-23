<?php

declare(strict_types=1);

namespace App\WorkSchedule\FormRequests;

use App\Attendance\AttendanceAccess;
use App\Team\Exceptions\CouldNotViewTeam;
use App\WorkSchedule\Actions\FindWorkSchedule;
use App\WorkSchedule\Actions\MakeWorkScheduleDefault;
use App\WorkSchedule\Exceptions\CouldNotFindWorkSchedule;
use App\WorkSchedule\WorkSchedule;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Makes a schedule the one members without their own follow. Owners and
 * admins only.
 */
final class MakeDefaultWorkScheduleFormRequest extends FormRequest
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

    public function process(): RedirectResponse
    {
        MakeWorkScheduleDefault::fromWorkSchedule($this->schedule);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name is now the default.', ['name' => $this->schedule->getName()->toString()])]);

        return back();
    }
}
