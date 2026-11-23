<?php

declare(strict_types=1);

namespace App\LeaveRequest\FormRequests;

use App\Attendance\AttendanceTimezone;
use App\LeaveRequest\Actions\ChangeLeaveStatus;
use App\LeaveRequest\Actions\FindLeaveRequest;
use App\LeaveRequest\Exceptions\CouldNotFindLeaveRequest;
use App\LeaveRequest\LeaveRequest;
use App\LeaveRequest\LeaveStatusEnum;
use App\Team\Actions\FindTeam;
use App\Team\Team;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Withdraws leave. Members can cancel their own while it waits, or once
 * approved until it starts; owners and admins can cancel anyone's.
 */
final class CancelLeaveRequestFormRequest extends FormRequest
{
    public LeaveRequest $leave;

    public Team $team;

    public function __construct()
    {
        parent::__construct();

        $this->leave = LeaveRequest::new();
        $this->team = Team::new();
    }

    /**
     * @throws CouldNotFindLeaveRequest
     */
    public function authorize(): Response
    {
        $this->team = FindTeam::byIdForMember($this->user()?->current_team_id, (string) $this->user()?->id);
        $this->leave = FindLeaveRequest::byIdForTeam((string) $this->route('leaveRequest'), $this->team->getId()->toNullableString());
        $isOwn = $this->leave->getUserId()->toNullableString() === (string) $this->user()?->id;

        if ($this->leave->isNull() || (! $isOwn && Gate::denies('manageAttendance', $this->team))) {
            throw CouldNotFindLeaveRequest::notFound();
        }

        return Response::allow();
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
            $leaveStatus = $this->leave->getStatus()->toEnum();

            if (! in_array($leaveStatus, [LeaveStatusEnum::Pending, LeaveStatusEnum::Approved], true)) {
                $validator->errors()->add('leave', __('This leave isn’t going ahead anyway.'));

                return;
            }

            if ($leaveStatus === LeaveStatusEnum::Approved && ! self::canCancelApproved($this->team, $this->leave)) {
                $validator->errors()->add('leave', __('This leave has started. Ask an owner or admin to change it.'));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        ChangeLeaveStatus::to($this->leave, LeaveStatusEnum::Cancelled);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Leave cancelled.')]);

        return back();
    }

    /**
     * Whether the signed-in person may cancel this approved leave: anyone
     * before its first day in the member's timezone, owners and admins after.
     */
    public static function canCancelApproved(Team $team, LeaveRequest $leave): bool
    {
        $today = CarbonImmutable::now()->setTimezone(AttendanceTimezone::forUserId($leave->getUserId()->toString()))->toDateString();

        return $leave->getStartsOn()->toDateString() > $today || Gate::allows('manageAttendance', $team);
    }
}
