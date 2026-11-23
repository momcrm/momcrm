<?php

declare(strict_types=1);

namespace App\Holiday\FormRequests;

use App\Attendance\AttendanceAccess;
use App\Holiday\Actions\DeleteHoliday;
use App\Holiday\Actions\FindHoliday;
use App\Holiday\Exceptions\CouldNotFindHoliday;
use App\Holiday\Holiday;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class DeleteHolidayFormRequest extends FormRequest
{
    public Holiday $holiday;

    public function __construct()
    {
        parent::__construct();

        $this->holiday = Holiday::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindHoliday
     */
    public function authorize(): Response
    {
        $this->holiday = FindHoliday::byIdForTeam((string) $this->route('holiday'), AttendanceAccess::team($this)->getId()->toString());

        if ($this->holiday->isNull()) {
            throw CouldNotFindHoliday::notFound();
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
        DeleteHoliday::fromHoliday($this->holiday);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name removed.', ['name' => $this->holiday->getName()->toString()])]);

        return back();
    }
}
