<?php

declare(strict_types=1);

namespace App\BankTransaction;

use App\BankTransaction\Actions\FindBankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * What the bank feed list is narrowed and sorted by, read from the query
 * string: a status (pending lines unless asked), an account, a search, a
 * posted-on range and a sort. Anything that isn't valid is ignored.
 */
final readonly class BankTransactionFilters
{
    public const string ALL = 'all';

    public function __construct(
        public ?BankTransactionStatusEnum $status = BankTransactionStatusEnum::Pending,
        public ?string $bankAccountId = null,
        public ?string $search = null,
        public ?string $postedFrom = null,
        public ?string $postedTo = null,
        public string $sort = 'posted_on',
        /** @var 'asc'|'desc' */
        public string $direction = 'desc',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $date = fn (string $key): ?string => preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->string($key)->toString()) === 1 ? $request->string($key)->toString() : null;
        $account = $request->string('account')->toString();
        $status = $request->string('status')->toString();

        return new self(
            status: $status === self::ALL ? null : BankTransactionStatusEnum::tryFrom($status) ?? BankTransactionStatusEnum::Pending,
            bankAccountId: Str::isUuid($account) ? $account : null,
            search: $request->string('search')->trim()->toString() ?: null,
            postedFrom: $date('from'),
            postedTo: $date('to'),
            sort: array_key_exists($request->string('sort')->toString(), FindBankTransaction::SORTS) ? $request->string('sort')->toString() : 'posted_on',
            direction: $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc',
        );
    }

    public function isFiltered(): bool
    {
        return $this->bankAccountId !== null || $this->search !== null || $this->postedFrom !== null || $this->postedTo !== null;
    }

    /**
     * @return array{status: string, account: ?string, search: ?string, from: ?string, to: ?string, sort: string, direction: string}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status === null ? self::ALL : $this->status->value,
            'account' => $this->bankAccountId,
            'search' => $this->search,
            'from' => $this->postedFrom,
            'to' => $this->postedTo,
            'sort' => $this->sort,
            'direction' => $this->direction,
        ];
    }
}
