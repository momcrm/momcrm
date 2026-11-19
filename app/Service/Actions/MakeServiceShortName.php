<?php

declare(strict_types=1);

namespace App\Service\Actions;

use App\Service\Properties\CustomerId;
use App\Service\Properties\ShortName;
use App\Service\ServiceEloquentModel;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Support\Str;

/**
 * Makes a short name for a service from its name, such as
 * "microsoft-365-business" for "Microsoft 365 Business", numbered when the
 * customer already has one by that name.
 */
final readonly class MakeServiceShortName
{
    public function __construct(
        private string $name,
        private string $customerId,
        private ?string $ignoreServiceId = null,
    ) {}

    public function __invoke(): string
    {
        $base = Str::limit(Str::slug($this->name), 240, '') ?: 'service';
        $taken = ServiceEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(CustomerId::getDatabaseTableColumnName(), $this->customerId)
            ->where(ShortName::getDatabaseTableColumnName(), 'like', $base.'%')
            ->when($this->ignoreServiceId, fn ($query, string $id) => $query->whereKeyNot($id))
            ->pluck(ShortName::getDatabaseTableColumnName())
            ->all();

        $shortName = $base;

        for ($number = 2; in_array($shortName, $taken, true); $number++) {
            $shortName = "{$base}-{$number}";
        }

        return $shortName;
    }

    public static function for(string $name, string $customerId, ?string $ignoreServiceId = null): string
    {
        return new self($name, $customerId, $ignoreServiceId)->handle();
    }

    public function handle(): string
    {
        return $this->__invoke();
    }

    public function execute(): string
    {
        return $this->__invoke();
    }
}
