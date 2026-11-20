import { Plus, X } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DEFAULT_CURRENCY } from '@/lib/money';
import type { Option, RatePrice } from '@/types';

type PriceRow = {
    key: number;
    currency: string;
    price: string;
    cost: string;
};

function dollars(cents: number | null | undefined): string {
    return cents == null ? '' : (cents / 100).toFixed(2);
}

function rowsFrom(prices: RatePrice[] | undefined): PriceRow[] {
    const rows = (prices ?? []).map((price, index) => ({
        key: index,
        currency: price.currency,
        price: dollars(price.price_in_cents),
        cost: dollars(price.cost_in_cents),
    }));

    return rows.length > 0
        ? rows
        : [{ key: 0, currency: DEFAULT_CURRENCY, price: '', cost: '' }];
}

/**
 * A rate's monthly price and cost in each currency it is sold in, one row per
 * currency, submitted as prices[n][currency|price|cost].
 */
export default function RatePricesField({
    prices,
    currencies,
    errors,
}: {
    prices?: RatePrice[];
    currencies: Option[];
    errors: Record<string, string | undefined>;
}) {
    const [rows, setRows] = useState<PriceRow[]>(() => rowsFrom(prices));
    const used = new Set(rows.map((row) => row.currency));
    const unused = currencies.filter((currency) => !used.has(currency.value));

    const change = (key: number, changes: Partial<PriceRow>) =>
        setRows((current) =>
            current.map((row) =>
                row.key === key ? { ...row, ...changes } : row,
            ),
        );

    return (
        <fieldset className="grid gap-2">
            <legend className="mb-1.5 text-sm leading-none font-medium">
                Price a month, per unit, before tax
            </legend>
            <div className="grid grid-cols-[minmax(0,7rem)_minmax(0,1fr)_minmax(0,1fr)_2rem] gap-x-2 text-xs text-muted-foreground">
                <span>Currency</span>
                <span>Price</span>
                <span>Cost (optional)</span>
            </div>
            {rows.map((row, index) => {
                const rowErrors = [
                    errors[`prices.${index}.currency`],
                    errors[`prices.${index}.price_in_cents`],
                    errors[`prices.${index}.cost_in_cents`],
                ].filter(Boolean);

                return (
                    <div key={row.key} className="grid gap-1">
                        <div className="grid grid-cols-[minmax(0,7rem)_minmax(0,1fr)_minmax(0,1fr)_2rem] items-center gap-x-2">
                            <NativeSelect
                                name={`prices[${index}][currency]`}
                                aria-label="Currency"
                                value={row.currency}
                                onChange={(event) =>
                                    change(row.key, {
                                        currency: event.target.value,
                                    })
                                }
                            >
                                {currencies
                                    .filter(
                                        (currency) =>
                                            currency.value === row.currency ||
                                            !used.has(currency.value),
                                    )
                                    .map((currency) => (
                                        <option
                                            key={currency.value}
                                            value={currency.value}
                                            title={currency.label}
                                        >
                                            {currency.value}
                                        </option>
                                    ))}
                            </NativeSelect>
                            <Input
                                name={`prices[${index}][price]`}
                                aria-label={`Price in ${row.currency}`}
                                type="number"
                                min="0"
                                step="0.01"
                                inputMode="decimal"
                                value={row.price}
                                onChange={(event) =>
                                    change(row.key, {
                                        price: event.target.value,
                                    })
                                }
                                required
                            />
                            <Input
                                name={`prices[${index}][cost]`}
                                aria-label={`Cost in ${row.currency}`}
                                type="number"
                                min="0"
                                step="0.01"
                                inputMode="decimal"
                                value={row.cost}
                                onChange={(event) =>
                                    change(row.key, {
                                        cost: event.target.value,
                                    })
                                }
                            />
                            {rows.length > 1 ? (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label={`Remove the ${row.currency} price`}
                                    onClick={() =>
                                        setRows((current) =>
                                            current.filter(
                                                (other) =>
                                                    other.key !== row.key,
                                            ),
                                        )
                                    }
                                >
                                    <X />
                                </Button>
                            ) : (
                                <span />
                            )}
                        </div>
                        {rowErrors.map((error) => (
                            <InputError key={error} message={error} />
                        ))}
                    </div>
                );
            })}
            <InputError message={errors.prices} />
            {unused.length > 0 && (
                <div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="-ml-2 h-7"
                        onClick={() =>
                            setRows((current) => [
                                ...current,
                                {
                                    key:
                                        Math.max(
                                            ...current.map((row) => row.key),
                                        ) + 1,
                                    currency: unused[0].value,
                                    price: '',
                                    cost: '',
                                },
                            ])
                        }
                    >
                        <Plus />
                        Add a currency
                    </Button>
                </div>
            )}
        </fieldset>
    );
}
