<?php

declare(strict_types=1);

namespace App\LeaveRequest\FormRequests;

use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\LeaveRequest\Actions\CreateLeaveRequest;
use App\LeaveRequest\Actions\FindLeaveRequest;
use App\LeaveRequest\Actions\NotifyLeaveApprovers;
use App\LeaveRequest\LeavePartEnum;
use App\LeaveRequest\LeaveRequest;
use App\LeaveRequest\LeaveStatusEnum;
use App\LeaveRequest\Properties\EndsOn;
use App\LeaveRequest\Properties\LeaveTypeId;
use App\LeaveRequest\Properties\Part;
use App\LeaveRequest\Properties\Reason;
use App\LeaveRequest\Properties\StartsOn;
use App\LeaveRequest\Properties\UserId;
use App\Shared\Table;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Asks for time off, which waits for an owner or admin. Owners and admins
 * can also record leave for someone else, such as a sick day phoned in,
 * which is approved straight away.
 */
final class StoreLeaveRequestFormRequest extends FormRequest
{
    public const int MAX_DAYS = 366;

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

        return $this->isForSomeoneElse()
            ? Gate::authorize('manageAttendance', $this->team)
            : Response::allow();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $teamId = $this->team->getId()->toString();

        return [
            UserId::getNameForValidation() => ['nullable', 'uuid', Rule::exists(Table::TeamUser->value, 'user_id')->where('team_id', $teamId)],
            LeaveTypeId::getNameForValidation() => ['required', 'uuid', Rule::exists(Table::Choices->value, 'id')
                ->where('team_id', $teamId)
                ->where('list', ChoiceListEnum::LeaveType->value)
                ->whereNull('archived_at')],
            StartsOn::getNameForValidation() => ['required', 'date_format:Y-m-d'],
            EndsOn::getNameForValidation() => ['required', 'date_format:Y-m-d', 'after_or_equal:'.StartsOn::getNameForValidation()],
            Part::getNameForValidation() => ['required', Rule::enum(LeavePartEnum::class)],
            Reason::getNameForValidation() => ['nullable', 'string', 'max:'.Reason::MAX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            LeaveTypeId::getNameForValidation() => __('type of leave'),
            StartsOn::getNameForValidation() => __('first day'),
            EndsOn::getNameForValidation() => __('last day'),
        ];
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

            $startsOn = $this->string(StartsOn::getNameForValidation())->toString();
            $endsOn = $this->string(EndsOn::getNameForValidation())->toString();
            $part = LeavePartEnum::from($this->string(Part::getNameForValidation())->toString());

            if ($part->isHalfDay() && $startsOn !== $endsOn) {
                $validator->errors()->add(Part::getNameForValidation(), __('Half a day is for one day only.'));
            } elseif (CarbonImmutable::parse($startsOn)->diffInDays(CarbonImmutable::parse($endsOn)) >= self::MAX_DAYS) {
                $validator->errors()->add(EndsOn::getNameForValidation(), __('Leave can’t be longer than a year.'));
            } elseif (FindLeaveRequest::overlapsForMember($this->team->getId()->toString(), $this->memberId(), $startsOn, $endsOn, $part)) {
                $validator->errors()->add(StartsOn::getNameForValidation(), $this->isForSomeoneElse()
                    ? __('They already have leave on some of these days.')
                    : __('You already have leave on some of these days.'));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $teamId = $this->team->getId()->toString();
        $reviewerId = (string) $this->user()?->id;
        $recorded = $this->isForSomeoneElse();

        $leave = CreateLeaveRequest::fromLeaveRequest(LeaveRequest::new()
            ->setTeamId($teamId)
            ->setUserId($this->memberId())
            ->setLeaveTypeId($this->string(LeaveTypeId::getNameForValidation())->toString())
            ->setStartsOn($this->string(StartsOn::getNameForValidation())->toString())
            ->setEndsOn($this->string(EndsOn::getNameForValidation())->toString())
            ->setPart($this->string(Part::getNameForValidation())->toString())
            ->setReason($this->filled(Reason::getNameForValidation()) ? $this->string(Reason::getNameForValidation())->trim()->toString() : null)
            ->setStatus($recorded ? LeaveStatusEnum::Approved : LeaveStatusEnum::Pending)
            ->setReviewedBy($recorded ? $reviewerId : null)
            ->setReviewedAt($recorded ? CarbonImmutable::now()->utc() : null));

        $leaveType = FindChoice::byIdForTeam($leave->getLeaveTypeId()->toString(), $teamId)->getName()->toString();

        if ($recorded) {
            NotifyLeaveApprovers::ofDecision($leave, $reviewerId, $leaveType);
        } else {
            NotifyLeaveApprovers::ofRequest($leave, $leaveType);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $recorded ? __('Leave recorded.') : __('Leave requested.')]);

        return back();
    }

    private function memberId(): string
    {
        $userId = $this->input(UserId::getNameForValidation());

        return is_string($userId) && $userId !== '' ? $userId : (string) $this->user()?->id;
    }

    private function isForSomeoneElse(): bool
    {
        return $this->memberId() !== (string) $this->user()?->id;
    }
}
