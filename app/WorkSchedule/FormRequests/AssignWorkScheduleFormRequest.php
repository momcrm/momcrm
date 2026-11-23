<?php

declare(strict_types=1);

namespace App\WorkSchedule\FormRequests;

use App\Attendance\AttendanceAccess;
use App\Shared\Table;
use App\Team\Exceptions\CouldNotViewTeam;
use App\TeamUser\Actions\AssignWorkSchedule;
use App\TeamUser\Actions\FindTeamUser;
use App\TeamUser\Properties\WorkScheduleId;
use App\WorkSchedule\Properties\Id;
use App\WorkSchedule\Properties\TeamId;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Puts a member on one of the team's schedules, or back on the default.
 * Owners and admins only.
 */
final class AssignWorkScheduleFormRequest extends FormRequest
{
    public string $teamId = '';

    /**
     * @throws CouldNotViewTeam
     * @throws NotFoundHttpException
     */
    public function authorize(): Response
    {
        $this->teamId = AttendanceAccess::team($this)->getId()->toString();

        if (FindTeamUser::byTeamAndUser($this->teamId, (string) $this->route('member'))->isNull()) {
            throw new NotFoundHttpException('Member not found.');
        }

        return AttendanceAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            WorkScheduleId::getNameForValidation() => ['nullable', 'uuid', Rule::exists(Table::WorkSchedules->value, Id::getDatabaseTableColumnName())
                ->where(TeamId::getDatabaseTableColumnName(), $this->teamId)],
        ];
    }

    public function process(): RedirectResponse
    {
        $scheduleId = $this->input(WorkScheduleId::getNameForValidation());

        AssignWorkSchedule::forMember($this->teamId, (string) $this->route('member'), is_string($scheduleId) && $scheduleId !== '' ? $scheduleId : null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Schedule saved.')]);

        return back();
    }
}
