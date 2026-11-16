<?php

declare(strict_types=1);

namespace App\Customer\Actions;

use App\Contact\ContactEloquentModel;
use App\Contact\Properties\CustomerId as ContactCustomerId;
use App\Contact\Properties\Id as ContactId;
use App\Contact\Properties\IsPrimary as ContactIsPrimary;
use App\Customer\Customer;
use App\Location\LocationEloquentModel;
use App\Location\Properties\CustomerId as LocationCustomerId;
use App\Location\Properties\Id as LocationId;
use App\Location\Properties\IsPrimary as LocationIsPrimary;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Makes one of the customer's contacts its main contact, and one of its
 * locations its main location; the rest stop being main. Null leaves the
 * customer with none.
 */
final readonly class SetMainContactAndLocation
{
    public function __construct(
        private Customer $customer,
        private ?string $contactId,
        private ?string $locationId,
    ) {}

    public function __invoke(): void
    {
        $customerId = $this->customer->getId()->toString();

        DB::transaction(function () use ($customerId): void {
            self::mark(
                ContactEloquentModel::query()->withoutGlobalScope(CurrentTeamScope::class)->where(ContactCustomerId::getDatabaseTableColumnName(), $customerId),
                ContactId::getDatabaseTableColumnName(),
                ContactIsPrimary::getDatabaseTableColumnName(),
                $this->contactId,
            );

            self::mark(
                LocationEloquentModel::query()->withoutGlobalScope(CurrentTeamScope::class)->where(LocationCustomerId::getDatabaseTableColumnName(), $customerId),
                LocationId::getDatabaseTableColumnName(),
                LocationIsPrimary::getDatabaseTableColumnName(),
                $this->locationId,
            );
        });
    }

    public static function forCustomer(Customer $customer, ?string $contactId, ?string $locationId): void
    {
        new self($customer, $contactId, $locationId)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }

    /**
     * Saves each record that changes one by one, so its audit log sees it.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $records
     */
    private static function mark(Builder $records, string $idColumn, string $primaryColumn, ?string $mainId): void
    {
        $records->get()->each(function (Model $record) use ($idColumn, $primaryColumn, $mainId): void {
            $isMain = $record->getAttribute($idColumn) === $mainId;

            if ((bool) $record->getAttribute($primaryColumn) !== $isMain) {
                $record->update([$primaryColumn => $isMain]);
            }
        });
    }
}
