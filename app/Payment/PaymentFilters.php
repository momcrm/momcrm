<?php

declare(strict_types=1);

namespace App\Payment;

use App\Payment\Actions\FindPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * What a payments list is narrowed and sorted by, read from the query
 * string: a search, a method, a paid-on range and a sort. Anything that
 * isn't valid is ignored.
 */
final readonly class PaymentFilters
{
    public function __construct(
        public ?string $search = null,
        public ?PaymentMethodEnum $method = null,
        public ?string $paidFrom = null,
        public ?string $paidTo = null,
        public ?string $customerId = null,
        public string $sort = 'paid_on',
        /** @var 'asc'|'desc' */
        public string $direction = 'desc',
    ) {}

    public static function fromRequest(Request $request, ?string $customerId = null): self
    {
        $date = fn (string $key): ?string => preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->string($key)->toString()) === 1 ? $request->string($key)->toString() : null;
        $customer = $customerId ?? $request->string('customer')->toString();

        return new self(
            search: $request->string('search')->trim()->toString() ?: null,
            method: PaymentMethodEnum::tryFrom($request->string('method')->toString()),
            paidFrom: $date('from'),
            paidTo: $date('to'),
            customerId: Str::isUuid($customer) ? $customer : null,
            sort: array_key_exists($request->string('sort')->toString(), FindPayment::SORTS) ? $request->string('sort')->toString() : 'paid_on',
            direction: $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc',
        );
    }

    public function isFiltered(): bool
    {
        return $this->search !== null || $this->method !== null || $this->paidFrom !== null || $this->paidTo !== null;
    }

    /**
     * @return array{search: ?string, method: ?string, from: ?string, to: ?string, sort: string, direction: string}
     */
    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'method' => $this->method?->value,
            'from' => $this->paidFrom,
            'to' => $this->paidTo,
            'sort' => $this->sort,
            'direction' => $this->direction,
        ];
    }
}
