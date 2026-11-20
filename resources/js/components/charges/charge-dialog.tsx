import { Form } from '@inertiajs/react';
import { useState } from 'react';
import FormField from '@/components/form-field';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { store, update } from '@/routes/charges';
import type { Charge, Product } from '@/types';

/** Today as YYYY-MM-DD in the browser's time zone. */
function today(): string {
    const now = new Date();
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

/**
 * Charges a customer once, to go on their next invoice, or changes a charge
 * that isn't billed yet. Picking a product fills in its name and its price
 * in the customer's currency, both still editable.
 */
export default function ChargeDialog({
    open,
    onOpenChange,
    customerId,
    currency,
    products,
    charge,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    customerId: string;
    /** The customer's currency, which the charge is in. */
    currency: string;
    products: Product[];
    charge?: Charge;
}) {
    const priced = products.filter((product) =>
        product.prices.some((price) => price.currency === currency),
    );
    const [productId, setProductId] = useState(charge?.product_id ?? '');
    const [description, setDescription] = useState(charge?.description ?? '');
    const [unitPrice, setUnitPrice] = useState(
        charge ? (charge.unit_price_in_cents / 100).toFixed(2) : '',
    );

    const pickProduct = (id: string) => {
        setProductId(id);

        const product = priced.find((option) => option.id === id);
        const price = product?.prices.find(
            (option) => option.currency === currency,
        );

        if (product) {
            setDescription(product.name);
        }

        if (price) {
            setUnitPrice((price.price_in_cents / 100).toFixed(2));
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogTitle>
                    {charge ? 'Edit charge' : 'New one-off charge'}
                </DialogTitle>
                <DialogDescription>
                    It goes on their next invoice, whether you bill them now or
                    the monthly run does.
                </DialogDescription>
                <Form
                    {...(charge ? update.form(charge.id) : store.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <input
                                type="hidden"
                                name="customer_id"
                                value={customerId}
                            />
                            {priced.length > 0 && (
                                <FormField
                                    id="charge-product"
                                    label="Product"
                                    error={errors.product_id}
                                >
                                    <NativeSelect
                                        id="charge-product"
                                        name="product_id"
                                        value={productId}
                                        onChange={(event) =>
                                            pickProduct(event.target.value)
                                        }
                                    >
                                        <option value="">None, typed in</option>
                                        {priced.map((product) => (
                                            <option
                                                key={product.id}
                                                value={product.id}
                                            >
                                                {product.name}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                            )}
                            <FormField
                                id="charge-description"
                                label="Description"
                                error={errors.description}
                            >
                                <Input
                                    id="charge-description"
                                    name="description"
                                    value={description}
                                    onChange={(event) =>
                                        setDescription(event.target.value)
                                    }
                                    placeholder="Call-out, 2 hours"
                                    required
                                    autoFocus={priced.length === 0}
                                />
                            </FormField>
                            <div className="grid grid-cols-3 gap-4">
                                <FormField
                                    id="charge-quantity"
                                    label="Quantity"
                                    error={errors.quantity}
                                >
                                    <Input
                                        id="charge-quantity"
                                        name="quantity"
                                        type="number"
                                        min={1}
                                        defaultValue={charge?.quantity ?? 1}
                                        required
                                    />
                                </FormField>
                                <FormField
                                    id="charge-price"
                                    label={`Price (${currency})`}
                                    error={errors.unit_price_in_cents}
                                >
                                    <Input
                                        id="charge-price"
                                        name="unit_price"
                                        type="number"
                                        inputMode="decimal"
                                        step="0.01"
                                        min="0"
                                        value={unitPrice}
                                        onChange={(event) =>
                                            setUnitPrice(event.target.value)
                                        }
                                        required
                                    />
                                </FormField>
                                <FormField
                                    id="charge-on"
                                    label="Date"
                                    error={errors.charged_on}
                                >
                                    <Input
                                        id="charge-on"
                                        name="charged_on"
                                        type="date"
                                        defaultValue={
                                            charge?.charged_on ?? today()
                                        }
                                        required
                                    />
                                </FormField>
                            </div>
                            <InputError message={errors.charge} />
                            <DialogFooter>
                                <Button disabled={processing}>
                                    {processing && <Spinner />}
                                    {charge ? 'Save charge' : 'Add charge'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
