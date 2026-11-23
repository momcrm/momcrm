<?php

declare(strict_types=1);

namespace App\Shift;

use App\Shift\Actions\FindShift;
use App\Shift\Properties\ClockedInAt;
use App\Shift\Properties\ClockedOutAt;
use App\Shift\Properties\Note;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;

/**
 * The form an owner or admin fills in to add or correct a shift. Times are
 * wall-clock times in the member's timezone, as the member would read them.
 */
final readonly class ShiftCorrection
{
    public const string BREAKS = 'breaks';

    private const string LOCAL_FORMAT = 'Y-m-d\TH:i';

    private const int LONGEST_SHIFT_HOURS = 24;

    public function __construct(
        private string $teamId,
        private string $userId,
        private string $timezone,
        private ?string $shiftId = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            ClockedInAt::getNameForValidation() => ['required', 'date_format:'.self::LOCAL_FORMAT],
            ClockedOutAt::getNameForValidation() => ['nullable', 'date_format:'.self::LOCAL_FORMAT],
            Note::getNameForValidation() => ['nullable', 'string', 'max:255'],
            self::BREAKS => ['array', 'max:20'],
            self::BREAKS.'.*.started_at' => ['required', 'date_format:'.self::LOCAL_FORMAT],
            self::BREAKS.'.*.ended_at' => ['nullable', 'date_format:'.self::LOCAL_FORMAT],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            ClockedInAt::getNameForValidation() => 'clock-in',
            ClockedOutAt::getNameForValidation() => 'clock-out',
            self::BREAKS.'.*.started_at' => 'break start',
            self::BREAKS.'.*.ended_at' => 'break end',
        ];
    }

    /**
     * Checks the times make sense together: clock-out after clock-in, nothing
     * in the future, breaks inside the shift and apart from each other, and no
     * overlap with the member's other shifts.
     */
    public function check(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        /** @var array<string, mixed> $data */
        $data = $validator->getData();
        $now = CarbonImmutable::now()->addMinute();
        $in = $this->parse($data[ClockedInAt::getNameForValidation()] ?? null);
        $out = $this->parse($data[ClockedOutAt::getNameForValidation()] ?? null);

        if (! $in instanceof CarbonImmutable) {
            return;
        }

        if ($in->greaterThan($now)) {
            $validator->errors()->add(ClockedInAt::getNameForValidation(), __('The clock-in can’t be in the future.'));
        }

        if ($out instanceof CarbonImmutable) {
            if ($out->lessThanOrEqualTo($in)) {
                $validator->errors()->add(ClockedOutAt::getNameForValidation(), __('The clock-out must be after the clock-in.'));
            } elseif ($out->greaterThan($now)) {
                $validator->errors()->add(ClockedOutAt::getNameForValidation(), __('The clock-out can’t be in the future.'));
            } elseif ($in->diffInHours($out) > self::LONGEST_SHIFT_HOURS) {
                $validator->errors()->add(ClockedOutAt::getNameForValidation(), __('A shift can’t be longer than :hours hours.', ['hours' => self::LONGEST_SHIFT_HOURS]));
            }
        }

        $this->checkBreaks($validator, $this->breaks($data), $in, $out);

        if ($validator->errors()->isEmpty() && FindShift::overlapsForMember($this->teamId, $this->userId, $in, $out, $this->shiftId)) {
            $validator->errors()->add(ClockedInAt::getNameForValidation(), $out instanceof CarbonImmutable
                ? __('This overlaps another of their shifts.')
                : __('They already have a shift running at this time.'));
        }
    }

    /**
     * The clock-in, clock-out and breaks as UTC instants, after validation.
     *
     * @param  array<string, mixed>  $validated
     * @return array{clocked_in_at: CarbonImmutable, clocked_out_at: ?CarbonImmutable, note: ?string, breaks: list<array{started_at: CarbonImmutable, ended_at: ?CarbonImmutable}>}
     */
    public function values(array $validated): array
    {
        $note = $validated[Note::getNameForValidation()] ?? null;

        return [
            'clocked_in_at' => $this->parse($validated[ClockedInAt::getNameForValidation()] ?? null) ?? CarbonImmutable::now(),
            'clocked_out_at' => $this->parse($validated[ClockedOutAt::getNameForValidation()] ?? null),
            'note' => is_string($note) && $note !== '' ? $note : null,
            'breaks' => array_map(fn (array $break): array => ['started_at' => $break['started_at'], 'ended_at' => $break['ended_at']], $this->breaks($validated)),
        ];
    }

    /**
     * @param  list<array{index: int|string, started_at: CarbonImmutable, ended_at: ?CarbonImmutable}>  $breaks
     */
    private function checkBreaks(Validator $validator, array $breaks, CarbonImmutable $in, ?CarbonImmutable $out): void
    {
        $previousEnd = null;

        foreach ($breaks as $break) {
            $key = self::BREAKS.'.'.$break['index'];

            if ($break['started_at']->lessThan($in) || ($out instanceof CarbonImmutable && $break['started_at']->greaterThanOrEqualTo($out))) {
                $validator->errors()->add("{$key}.started_at", __('A break must start during the shift.'));

                continue;
            }

            if ($previousEnd === false || ($previousEnd instanceof CarbonImmutable && $break['started_at']->lessThan($previousEnd))) {
                $validator->errors()->add("{$key}.started_at", __('Breaks can’t overlap.'));

                continue;
            }

            if ($break['ended_at'] === null) {
                if ($out instanceof CarbonImmutable) {
                    $validator->errors()->add("{$key}.ended_at", __('Give the break an end, since the shift has finished.'));
                }

                $previousEnd = false;

                continue;
            }

            if ($break['ended_at']->lessThanOrEqualTo($break['started_at'])) {
                $validator->errors()->add("{$key}.ended_at", __('A break must end after it starts.'));
            } elseif ($out instanceof CarbonImmutable && $break['ended_at']->greaterThan($out)) {
                $validator->errors()->add("{$key}.ended_at", __('A break must end by the clock-out.'));
            }

            $previousEnd = $break['ended_at'];
        }
    }

    /**
     * The breaks given, earliest first, each with its place in the form so an
     * error lands on the right row.
     *
     * @param  array<string, mixed>  $data
     * @return list<array{index: int|string, started_at: CarbonImmutable, ended_at: ?CarbonImmutable}>
     */
    private function breaks(array $data): array
    {
        $given = $data[self::BREAKS] ?? [];
        $breaks = [];

        foreach (is_array($given) ? $given : [] as $index => $break) {
            $startedAt = is_array($break) ? $this->parse($break['started_at'] ?? null) : null;

            if (is_array($break) && $startedAt instanceof CarbonImmutable) {
                $breaks[] = ['index' => $index, 'started_at' => $startedAt, 'ended_at' => $this->parse($break['ended_at'] ?? null)];
            }
        }

        usort($breaks, fn (array $a, array $b): int => $a['started_at'] <=> $b['started_at']);

        return $breaks;
    }

    private function parse(mixed $local): ?CarbonImmutable
    {
        if (! is_string($local) || $local === '') {
            return null;
        }

        $parsed = CarbonImmutable::createFromFormat(self::LOCAL_FORMAT, $local, $this->timezone);

        return $parsed instanceof CarbonImmutable ? $parsed->utc() : null;
    }
}
