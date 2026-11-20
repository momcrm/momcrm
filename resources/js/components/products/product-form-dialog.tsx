import { Form } from '@inertiajs/react';
import FormField from '@/components/form-field';
import RatePricesField from '@/components/rates/rate-prices-field';
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
import { store, update } from '@/routes/products';
import type { Option, Product } from '@/types';

/**
 * Adds a product to the catalogue, or edits one.
 */
export default function ProductFormDialog({
    open,
    onOpenChange,
    currencies,
    product,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    currencies: Option[];
    product?: Product;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogTitle>
                    {product ? `Edit ${product.name}` : 'New product'}
                </DialogTitle>
                <DialogDescription>
                    Charged once, in the customer's currency. Changing a price
                    doesn't change charges already made.
                </DialogDescription>
                <Form
                    {...(product ? update.form(product.id) : store.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <FormField
                                id="product-name"
                                label="Name"
                                error={errors.name}
                            >
                                <Input
                                    id="product-name"
                                    name="name"
                                    defaultValue={product?.name ?? ''}
                                    placeholder="Business laptop setup"
                                    required
                                    autoFocus
                                />
                            </FormField>
                            <FormField
                                id="product-description"
                                label="Description"
                                error={errors.description}
                            >
                                <Input
                                    id="product-description"
                                    name="description"
                                    defaultValue={product?.description ?? ''}
                                />
                            </FormField>
                            <RatePricesField
                                prices={product?.prices}
                                currencies={currencies}
                                errors={errors}
                            />
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => onOpenChange(false)}
                                >
                                    Cancel
                                </Button>
                                <Button disabled={processing}>
                                    {processing && <Spinner />}
                                    {product ? 'Save product' : 'Add product'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
