<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Actions\FindService;
use App\Service\Exceptions\CouldNotFindService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * The services picked in the services list for a bulk change. Every one must
 * be a service of the team, or none are touched.
 */
final class BulkServiceSelection
{
    public const string SERVICE_IDS = 'service_ids';

    public const int MAX = 200;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            self::SERVICE_IDS => ['required', 'array', 'min:1', 'max:'.self::MAX],
            self::SERVICE_IDS.'.*' => ['uuid', 'distinct'],
        ];
    }

    /**
     * The picked services; none when the list itself is invalid, so the
     * rules can report it.
     *
     * @throws CouldNotFindService
     */
    public static function load(FormRequest $request): ServiceCollection
    {
        $input = $request->input(self::SERVICE_IDS);
        $ids = array_values(array_unique(array_filter(
            is_array($input) ? $input : [],
            fn (mixed $id): bool => is_string($id) && Str::isUuid($id),
        )));

        if ($ids === [] || count($ids) > self::MAX) {
            return new ServiceCollection([]);
        }

        $services = FindService::byIdsForTeam((string) $request->user()?->current_team_id, $ids);

        if ($services->count() !== count($ids)) {
            throw CouldNotFindService::notFound();
        }

        return $services;
    }
}
