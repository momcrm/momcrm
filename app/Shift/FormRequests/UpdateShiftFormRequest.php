<?php

declare(strict_types=1);

namespace App\Shift\FormRequests;

use App\Attendance\AttendanceTimezone;
use App\Shift\Actions\FindShift;
use App\Shift\Actions\SaveShift;
use App\Shift\Exceptions\CouldNotFindShift;
use App\Shift\Shift;
use App\Shift\ShiftCorrection;
use App\Team\Actions\FindTeam;
use App\Team\Team;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Corrects a member's shift: its times, note and breaks. Owners and admins
 * only; the audit log keeps what it was before.
 */
final class UpdateShiftFormRequest extends FormRequest
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
        return ShiftCorrection::rules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ShiftCorrection::attributes();
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->correction()->check($validator)];
    }

    public function process(): RedirectResponse
    {
        $values = $this->correction()->values($this->validated());

        SaveShift::fromShift($this->shift
            ->setClockedInAt($values['clocked_in_at'])
            ->setClockedOutAt($values['clocked_out_at'])
            ->setNote($values['note']), $values['breaks']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shift saved.')]);

        return back();
    }

    private function correction(): ShiftCorrection
    {
        $userId = $this->shift->getUserId()->toString();

        return new ShiftCorrection($this->shift->getTeamId()->toString(), $userId, AttendanceTimezone::forUserId($userId), $this->shift->getId()->toString(), $this->shift->getWorkDate()->toDateString());
    }
}
