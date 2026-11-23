<?php

declare(strict_types=1);

namespace App\WorkSchedule;

use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;

/**
 * A schedule's week: for each weekday, when work starts and ends and how long
 * the unpaid break is, or null for a day off. An end at or before the start
 * runs past midnight into the next day, for night shifts.
 *
 * @phpstan-type Day array{starts_at: string, ends_at: string, break_minutes: int}
 */
final class WorkScheduleDays
{
    /** Weekday keys, Monday first, as ISO weekday numbers 1 to 7 order them. */
    public const array KEYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public const int MAX_BREAK_MINUTES = 240;

    private const int MINUTES_IN_DAY = 1440;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        $rules = ['days' => ['required', 'array:'.implode(',', self::KEYS)]];

        foreach (self::KEYS as $key) {
            $rules["days.{$key}"] = ['nullable', 'array:starts_at,ends_at,break_minutes'];
            $rules["days.{$key}.starts_at"] = ["required_with:days.{$key}", 'date_format:H:i'];
            $rules["days.{$key}.ends_at"] = ["required_with:days.{$key}", 'date_format:H:i', "different:days.{$key}.starts_at"];
            $rules["days.{$key}.break_minutes"] = ['nullable', 'integer', 'min:0', 'max:'.self::MAX_BREAK_MINUTES];
        }

        return $rules;
    }

    /**
     * Checks what the rules can't: a schedule needs a working day, and each
     * break must be shorter than its day.
     */
    public static function check(Validator $validator, mixed $days): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $working = array_filter(self::fromInput($days));

        if ($working === []) {
            $validator->errors()->add('days', __('Choose at least one working day.'));

            return;
        }

        foreach ($working as $key => $day) {
            if ($day['break_minutes'] >= self::spanMinutes($day)) {
                $validator->errors()->add("days.{$key}.break_minutes", __('The break must be shorter than the day.'));
            }
        }
    }

    /**
     * Every weekday from submitted or stored days, with a day missing its
     * times taken as a day off.
     *
     * @return array<string, Day|null>
     */
    public static function fromInput(mixed $days): array
    {
        $week = [];

        foreach (self::KEYS as $key) {
            $day = is_array($days) ? ($days[$key] ?? null) : null;
            $startsAt = is_array($day) ? ($day['starts_at'] ?? null) : null;
            $endsAt = is_array($day) ? ($day['ends_at'] ?? null) : null;
            $breakMinutes = is_array($day) ? ($day['break_minutes'] ?? 0) : 0;

            $week[$key] = is_string($startsAt) && is_string($endsAt) && self::isTime($startsAt) && self::isTime($endsAt)
                ? ['starts_at' => $startsAt, 'ends_at' => $endsAt, 'break_minutes' => is_numeric($breakMinutes) ? max(0, (int) $breakMinutes) : 0]
                : null;
        }

        return $week;
    }

    /**
     * The hours for the weekday of this date, or null for a day off.
     *
     * @param  array<string, Day|null>  $days
     * @return Day|null
     */
    public static function forDate(array $days, CarbonImmutable $date): ?array
    {
        return $days[self::keyFor($date)] ?? null;
    }

    public static function keyFor(CarbonImmutable $date): string
    {
        return self::KEYS[$date->dayOfWeekIso - 1];
    }

    /**
     * Minutes from start to end, running past midnight when the end is earlier.
     *
     * @param  Day  $day
     */
    public static function spanMinutes(array $day): int
    {
        $minutes = self::minutesOf($day['ends_at']) - self::minutesOf($day['starts_at']);

        return $minutes > 0 ? $minutes : $minutes + self::MINUTES_IN_DAY;
    }

    /**
     * Minutes expected to be worked: the span less the break.
     *
     * @param  Day  $day
     */
    public static function expectedMinutes(array $day): int
    {
        return max(0, self::spanMinutes($day) - $day['break_minutes']);
    }

    private static function minutesOf(string $time): int
    {
        [$hours, $minutes] = array_map(intval(...), explode(':', $time));

        return $hours * 60 + $minutes;
    }

    private static function isTime(string $time): bool
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }
}
