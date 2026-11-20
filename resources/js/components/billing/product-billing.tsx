import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import BillingDone from '@/components/billing/billing-done';
import Stepper from '@/components/billing/stepper';
import FormField from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { formatCents } from '@/lib/money';
import { cn } from '@/lib/utils';
import { store } from '@/routes/charges';
import { billing } from '@/routes/customers';
import type { Product } from '@/types';

const STEPS = [
    { title: 'Parameters', description: 'Select product' },
    { title: 'Verify', description: 'Verify invoice item' },
    { title: 'Completed', description: 'Billing completed' },
];

/** Today as YYYY-MM-DD in the browser's time zone. */
function today(): string {
    const now = new Date();
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

/**
 * Product Billing, as Amphora's: pick a product, check or change its line,
 * and add it as a line that waits to be combined into an invoice.
 */
export default function ProductBilling({
    customerId,
    currency,
    products,
    canCharge,
}: {
    customerId: string;
    currency: string;
    products: Product[];
    canCharge: boolean;
}) {
    const priced = products.filter((product) =>
        product.prices.some((price) => price.currency === currency),
    );
    const [step, setStep] = useState(1);
    const [product, setProduct] = useState<Product | null>(null);
    const priceOf = (choice: Product) =>
        choice.prices.find((price) => price.currency === currency)
            ?.price_in_cents ?? 0;

    if (priced.length === 0) {
        return (
            <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                No products are priced in {currency}. Add them under Settings →
                Products.
            </p>
        );
    }

    return (
        <div>
            <Stepper steps={STEPS} current={step} />

            {step === 1 && (
                <div className="space-y-4">
                    <ul className="divide-y rounded-lg border bg-card">
                        {priced.map((choice) => (
                            <li key={choice.id}>
                                <button
                                    type="button"
                                    onClick={() => setProduct(choice)}
                                    className={cn(
                                        'flex w-full items-center gap-3 px-3 py-2 text-left text-sm hover:bg-muted/60',
                                        product?.id === choice.id &&
                                            'bg-primary/10',
                                    )}
                                >
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate font-medium">
                                            {choice.name}
                                        </span>
                                        {choice.description && (
                                            <span className="block truncate text-xs text-muted-foreground">
                                                {choice.description}
                                            </span>
                                        )}
                                    </span>
                                    <span className="tabular-nums">
                                        {formatCents(priceOf(choice), currency)}
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                    <Button disabled={!product} onClick={() => setStep(2)}>
                        Verify
                    </Button>
                </div>
            )}

            {step === 2 && product && (
                <Form
                    {...store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setStep(3)}
                    className="max-w-xl space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <input
                                type="hidden"
                                name="customer_id"
                                value={customerId}
                            />
                            <input
                                type="hidden"
                                name="product_id"
                                value={product.id}
                            />
                            <FormField
                                id="product-line-description"
                                label="Description"
                                error={errors.description}
                            >
                                <Input
                                    id="product-line-description"
                                    name="description"
                                    defaultValue={product.name}
                                    required
                                />
                            </FormField>
                            <div className="grid grid-cols-3 gap-4">
                                <FormField
                                    id="product-line-quantity"
                                    label="Quantity"
                                    error={errors.quantity}
                                >
                                    <Input
                                        id="product-line-quantity"
                                        name="quantity"
                                        type="number"
                                        min={1}
                                        defaultValue={1}
                                        required
                                    />
                                </FormField>
                                <FormField
                                    id="product-line-price"
                                    label={`Price (${currency})`}
                                    error={errors.unit_price_in_cents}
                                >
                                    <Input
                                        id="product-line-price"
                                        name="unit_price"
                                        type="number"
                                        inputMode="decimal"
                                        step="0.01"
                                        min="0"
                                        defaultValue={(
                                            priceOf(product) / 100
                                        ).toFixed(2)}
                                        required
                                    />
                                </FormField>
                                <FormField
                                    id="product-line-date"
                                    label="Date"
                                    error={errors.charged_on}
                                >
                                    <Input
                                        id="product-line-date"
                                        name="charged_on"
                                        type="date"
                                        defaultValue={today()}
                                        required
                                    />
                                </FormField>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setStep(1)}
                                >
                                    Back
                                </Button>
                                {canCharge && (
                                    <Button disabled={processing}>
                                        {processing && <Spinner />}
                                        Add to waiting lines
                                    </Button>
                                )}
                            </div>
                        </>
                    )}
                </Form>
            )}

            {step === 3 && (
                <BillingDone
                    title={`${product?.name ?? 'The product'} is waiting to be invoiced.`}
                    actions={
                        <>
                            <Button asChild>
                                <Link
                                    href={billing({
                                        customer: customerId,
                                        page: 'combine',
                                    })}
                                >
                                    Combine waiting lines
                                </Link>
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setProduct(null);
                                    setStep(1);
                                }}
                            >
                                Bill another
                            </Button>
                        </>
                    }
                >
                    It goes on their next invoice: combine it now, or the
                    monthly run picks it up.
                </BillingDone>
            )}
        </div>
    );
}
