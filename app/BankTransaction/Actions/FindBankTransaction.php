<?php

declare(strict_types=1);

namespace App\BankTransaction\Actions;

use App\BankTransaction\BankTransaction;
use App\BankTransaction\BankTransactionCollection;
use App\BankTransaction\BankTransactionEloquentModel;
use App\BankTransaction\BankTransactionFilters;
use App\BankTransaction\BankTransactionRelationship;
use App\BankTransaction\BankTransactionStatusEnum;
use App\BankTransaction\Properties\AmountInCents;
use App\BankTransaction\Properties\BankAccountId;
use App\BankTransaction\Properties\Crn;
use App\BankTransaction\Properties\Description;
use App\BankTransaction\Properties\HiddenAt;
use App\BankTransaction\Properties\Id;
use App\BankTransaction\Properties\PayerName;
use App\BankTransaction\Properties\PostedOn;
use App\BankTransaction\Properties\TeamId;
use App\Customer\Properties\Name as CustomerName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class FindBankTransaction
{
    /**
     * The columns the bank feed list can be sorted by.
     */
    public const array SORTS = [
        'posted_on' => 'posted_on',
        'amount_in_cents' => 'amount_in_cents',
    ];

    /**
     * A page of the team's bank feed lines, with their account and customer,
     * filtered and sorted as asked.
     *
     * @return LengthAwarePaginator<int, BankTransaction>
     */
    public static function paginateForTeam(string $teamId, BankTransactionFilters $filters, int $perPage = 50): LengthAwarePaginator
    {
        $direction = $filters->direction;

        return self::query($teamId)
            ->with([BankTransactionRelationship::BankAccount->value, BankTransactionRelationship::Customer->value])
            ->when($filters->status === BankTransactionStatusEnum::Pending, fn (Builder $query) => $query->whereNull(HiddenAt::getDatabaseTableColumnName()))
            ->when($filters->status === BankTransactionStatusEnum::Hidden, fn (Builder $query) => $query->whereNotNull(HiddenAt::getDatabaseTableColumnName()))
            ->when($filters->bankAccountId !== null, fn (Builder $query) => $query->where(BankAccountId::getDatabaseTableColumnName(), $filters->bankAccountId))
            ->when($filters->postedFrom !== null, fn (Builder $query) => $query->whereDate(PostedOn::getDatabaseTableColumnName(), '>=', (string) $filters->postedFrom))
            ->when($filters->postedTo !== null, fn (Builder $query) => $query->whereDate(PostedOn::getDatabaseTableColumnName(), '<=', (string) $filters->postedTo))
            ->when($filters->search !== null, function (Builder $query) use ($filters): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $filters->search).'%';

                $query->where(fn (Builder $inner) => $inner
                    ->where(Description::getDatabaseTableColumnName(), 'like', $like)
                    ->orWhere(PayerName::getDatabaseTableColumnName(), 'like', $like)
                    ->orWhere(Crn::getDatabaseTableColumnName(), 'like', $like)
                    ->orWhereHas(BankTransactionRelationship::Customer->value, fn (Builder $customer) => $customer->withoutGlobalScopes()->where(CustomerName::getDatabaseTableColumnName(), 'like', $like))
                    ->when(self::cents($filters->search) !== null, fn (Builder $amount) => $amount->orWhere(AmountInCents::getDatabaseTableColumnName(), self::cents($filters->search))));
            })
            ->orderBy(self::SORTS[$filters->sort], $direction)
            ->orderBy(Id::getDatabaseTableColumnName(), $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (BankTransactionEloquentModel $model): BankTransaction => BankTransaction::fromEloquentModel($model));
    }

    /**
     * The team's lines among the ids given, with their account.
     *
     * @param  list<string>  $ids
     */
    public static function byIdsForTeam(string $teamId, array $ids): BankTransactionCollection
    {
        if ($ids === []) {
            return new BankTransactionCollection([]);
        }

        return BankTransaction::collect(
            self::query($teamId)
                ->with(BankTransactionRelationship::BankAccount->value)
                ->whereKey($ids)
                ->get()
                ->all(),
            'fromEloquentModel',
        );
    }

    /**
     * How many lines have been imported into an account.
     */
    public static function countForAccount(string $bankAccountId): int
    {
        return BankTransactionEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(BankAccountId::getDatabaseTableColumnName(), $bankAccountId)
            ->count();
    }

    /**
     * A search typed as an amount ("1250", "$1,250.00"), in cents.
     */
    private static function cents(?string $search): ?int
    {
        if ($search === null || preg_match('/^\$?[\d,]+(\.\d{1,2})?$/', $search) !== 1) {
            return null;
        }

        return (int) round(((float) str_replace(['$', ','], '', $search)) * 100);
    }

    /**
     * @return Builder<BankTransactionEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return BankTransactionEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
