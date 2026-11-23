<?php

declare(strict_types=1);

namespace App\Shift\FormRequests;

use App\Shift\Actions\ClockOut;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Ends the signed-in member's shift, and their break if they are on one.
 */
final class ClockOutFormRequest extends FormRequest
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
        return [];
    }

    public function process(): RedirectResponse
    {
        if (ClockOut::forMember($this->team->getId()->toString(), (string) $this->user()?->id)->isNull()) {
            throw ValidationException::withMessages(['attendance' => __('You aren’t clocked in.')]);
        }

        return back();
    }
}
