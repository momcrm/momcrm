<?php

declare(strict_types=1);

namespace App\LeaveRequest\FormRequests;

use App\Choice\Actions\FindChoice;
use App\LeaveRequest\Actions\ChangeLeaveStatus;
use App\LeaveRequest\Actions\FindLeaveRequest;
use App\LeaveRequest\Actions\NotifyLeaveApprovers;
use App\LeaveRequest\Exceptions\CouldNotFindLeaveRequest;
use App\LeaveRequest\LeaveRequest;
use App\LeaveRequest\LeaveStatusEnum;
use App\LeaveRequest\Properties\ReviewNote;
use App\Team\Actions\FindTeam;
use App\Team\Team;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Approves a request still waiting for an answer, with an optional note, and
 * tells the member. Owners and admins only.
 */
final class ApproveLeaveRequestFormRequest extends FormRequest
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

        if ($this->leave->isNull()) {
            throw CouldNotFindLeaveRequest::notFound();
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
            if ($this->leave->getStatus()->toEnum() !== LeaveStatusEnum::Pending) {
                $validator->errors()->add('leave', __('This request has already been answered.'));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $reviewerId = (string) $this->user()?->id;
        $note = $this->filled(ReviewNote::getNameForValidation()) ? $this->string(ReviewNote::getNameForValidation())->trim()->toString() : null;
        $leave = ChangeLeaveStatus::to($this->leave, LeaveStatusEnum::Approved, $reviewerId, $note);

        NotifyLeaveApprovers::ofDecision($leave, $reviewerId, FindChoice::byIdForTeam($leave->getLeaveTypeId()->toString(), $this->team->getId()->toString())->getName()->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Leave approved.')]);

        return back();
    }
}
