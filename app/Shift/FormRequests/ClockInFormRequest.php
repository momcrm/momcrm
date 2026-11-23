<?php

declare(strict_types=1);

namespace App\Shift\FormRequests;

use App\Shift\Actions\ClockIn;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use App\User\Actions\FillTimezone;
use App\User\Properties\Timezone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Starts the signed-in member's shift in their current team. The browser
 * sends its timezone, which is saved for members who have not set one, so
 * their working day is counted where they are.
 */
final class ClockInFormRequest extends FormRequest
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
    public function authorize(): bool
    {
        $this->team = FindTeam::byIdForMember($this->user()?->current_team_id, (string) $this->user()?->id);

        if ($this->team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Timezone::getNameForValidation() => Timezone::getValidationRulesForCreate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        $userId = (string) $this->user()?->id;
        $timezone = $this->validated(Timezone::getNameForValidation());

        FillTimezone::whenMissing($userId, is_string($timezone) ? $timezone : null);
        ClockIn::forMember($this->team->getId()->toString(), $userId, $this->ip());

        return back();
    }
}
