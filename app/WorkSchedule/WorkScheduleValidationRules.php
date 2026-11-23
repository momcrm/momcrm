<?php

declare(strict_types=1);

namespace App\WorkSchedule;

use App\WorkSchedule\Properties\GraceMinutes;
use App\WorkSchedule\Properties\Name;
use Illuminate\Http\Request;

/**
 * What a schedule's form must hold, shared by adding and changing one.
 */
final class WorkScheduleValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            Name::getNameForValidation() => ['required', 'string', 'max:'.Name::MAX],
            GraceMinutes::getNameForValidation() => ['required', 'integer', 'min:0', 'max:'.GraceMinutes::MAX],
            ...WorkScheduleDays::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        $attributes = [GraceMinutes::getNameForValidation() => __('grace')];

        foreach (WorkScheduleDays::KEYS as $key) {
            $attributes["days.{$key}.starts_at"] = __('start');
            $attributes["days.{$key}.ends_at"] = __('end');
            $attributes["days.{$key}.break_minutes"] = __('break');
        }

        return $attributes;
    }

    /**
     * The form leaves out days off, so with none worked there are no days.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return ['days.required' => __('Choose at least one working day.')];
    }

    /**
     * The submitted form as a schedule's fields.
     */
    public static function fill(WorkSchedule $schedule, Request $request): WorkSchedule
    {
        return $schedule
            ->setName($request->string(Name::getNameForValidation())->trim()->toString())
            ->setGraceMinutes($request->integer(GraceMinutes::getNameForValidation()))
            ->setDays(WorkScheduleDays::fromInput($request->input('days')));
    }
}
