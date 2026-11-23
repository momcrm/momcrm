<?php

declare(strict_types=1);

namespace App\Shift\FormRequests;

use App\Attendance\AttendanceTimezone;
use App\Shared\Table;
use App\Shift\Actions\SaveShift;
use App\Shift\Properties\UserId;
use App\Shift\Shift;
use App\Shift\ShiftCorrection;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use App\TeamUser\Properties\TeamId as TeamUserTeamId;
use App\TeamUser\Properties\UserId as TeamUserUserId;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Adds a shift a member forgot to clock, on their behalf. Owners and admins only.
 */
final class StoreShiftFormRequest extends FormRequest
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
        $this->team = FindTeam::byIdForMember($this->user()?->current_team_id, (string) $this->user()?->id);

        if ($this->team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        return Gate::authorize('manageAttendance', $this->team);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            UserId::getNameForValidation() => ['required', 'uuid', Rule::exists(Table::TeamUser->value, TeamUserUserId::getDatabaseTableColumnName())
                ->where(TeamUserTeamId::getDatabaseTableColumnName(), $this->team->getId()->toString())],
            ...ShiftCorrection::rules(),
        ];
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

        SaveShift::fromShift(Shift::new()
            ->setTeamId($this->team->getId())
            ->setUserId($this->memberId())
            ->setClockedInAt($values['clocked_in_at'])
            ->setClockedOutAt($values['clocked_out_at'])
            ->setNote($values['note']), $values['breaks']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shift added.')]);

        return back();
    }

    private function correction(): ShiftCorrection
    {
        return new ShiftCorrection($this->team->getId()->toString(), $this->memberId(), AttendanceTimezone::forUserId($this->memberId()));
    }

    private function memberId(): string
    {
        $userId = $this->input(UserId::getNameForValidation());

        return is_string($userId) ? $userId : '';
    }
}
