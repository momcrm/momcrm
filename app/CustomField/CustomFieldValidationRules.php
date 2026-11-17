<?php

declare(strict_types=1);

namespace App\CustomField;

use App\CustomField\Properties\Options;
use App\Shared\HuePalette;
use Illuminate\Support\Str;

/**
 * The rules shared by adding and editing a custom field.
 */
final class CustomFieldValidationRules
{
    public const int MAX_OPTIONS = 50;

    /**
     * Rules for a field's options: a select needs at least one, and other
     * types take none.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function options(?CustomFieldTypeEnum $type, bool $isUpdate = false): array
    {
        $key = Options::getNameForValidation();

        if ($type?->hasOptions() !== true) {
            return [$key => ['prohibited']];
        }

        return [
            $key => [$isUpdate ? 'sometimes' : 'required', 'array', 'min:1', 'max:'.self::MAX_OPTIONS],
            "{$key}.*.id" => ['nullable', 'uuid'],
            "{$key}.*.name" => ['required', 'string', 'max:50', 'distinct:ignore_case'],
            "{$key}.*.hue" => ['nullable', 'integer', 'between:0,359'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        $key = Options::getNameForValidation();

        return [
            "{$key}.required" => __('Add at least one option.'),
            "{$key}.min" => __('Add at least one option.'),
            "{$key}.prohibited" => __('Only select fields have options.'),
            "{$key}.*.name.required" => __('Give every option a name.'),
            "{$key}.*.name.distinct" => __('Two options have the same name.'),
        ];
    }

    /**
     * Validated options as they are stored. An option the field already has
     * keeps its id, so records that picked it still show it; any other gets
     * a new one.
     *
     * @param  list<array{id: string, name: string, hue: ?int}>  $existing
     * @return list<array{id: string, name: string, hue: ?int}>
     */
    public static function normalizeOptions(mixed $input, array $existing = []): array
    {
        $knownIds = array_column($existing, 'id');
        $options = [];

        foreach (is_array($input) ? $input : [] as $option) {
            if (! is_array($option) || ! is_string($option['name'] ?? null)) {
                continue;
            }

            $id = $option['id'] ?? null;
            $name = trim($option['name']);
            $hue = $option['hue'] ?? null;

            $options[] = [
                'id' => is_string($id) && in_array($id, $knownIds, true) ? $id : (string) Str::uuid7(),
                'name' => $name,
                'hue' => array_key_exists('hue', $option)
                    ? (is_numeric($hue) ? (int) $hue : null)
                    : HuePalette::forName('custom_field_option', $name),
            ];
        }

        return $options;
    }
}
