import { Form } from '@inertiajs/react';
import { useState } from 'react';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import { billingFrequencyLabel } from '@/components/rates/billing-period';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store, update } from '@/routes/rates';
import type { Rate } from '@/types';

function dollars(cents: number | null | undefined): string {
    return cents == null ? '' : (cents / 100).toFixed(2);
}

/**
 * Adds a rate to the price list, or edits one.
 */
export default function RateFormDialog({
    open,
    onOpenChange,
    billingPeriods,
    rate,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    billingPeriods: number[];
    rate?: Rate;
}) {
    const [prorates, setProrates] = useState(
        rate?.prorates_first_month ?? true,
    );

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogTitle>
                    {rate ? `Edit ${rate.name}` : 'New rate'}
                </DialogTitle>
                <DialogDescription>
                    Services are priced from a rate: its monthly price, times
                    the quantity, times the months billed at once.
                </DialogDescription>
                <Form
                    {...(rate ? update.form(rate.id) : store.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <FormField
                                id="rate-name"
                                label="Name"
                                error={errors.name}
                            >
                                <Input
                                    id="rate-name"
                                    name="name"
                                    defaultValue={rate?.name ?? ''}
                                    placeholder="Managed IT support, per seat"
                                    required
                                    autoFocus
                                />
                            </FormField>
                            <FormField
                                id="rate-description"
                                label="Description"
                                error={errors.description}
                            >
                                <Input
                                    id="rate-description"
                                    name="description"
                                    defaultValue={rate?.description ?? ''}
                                />
                            </FormField>
                            <div className="grid gap-5 sm:grid-cols-2">
                                <FormField
                                    id="rate-price"
                                    label="Price a month (AUD, ex GST)"
                                    hint="Per unit."
                                    error={errors.price_in_cents}
                                >
                                    <Input
                                        id="rate-price"
                                        name="price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        inputMode="decimal"
                                        defaultValue={dollars(
                                            rate?.price_in_cents,
                                        )}
                                        required
                                    />
                                </FormField>
                                <FormField
                                    id="rate-cost"
                                    label="Cost a month"
                                    hint="Optional, what it costs you."
                                    error={errors.cost_in_cents}
                                >
                                    <Input
                                        id="rate-cost"
                                        name="cost"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        inputMode="decimal"
                                        defaultValue={dollars(
                                            rate?.cost_in_cents,
                                        )}
                                    />
                                </FormField>
                            </div>
                            <FormField
                                id="rate-billing-period"
                                label="Billed"
                                hint="A service can bill differently."
                                error={errors.billing_period_in_months}
                            >
                                <NativeSelect
                                    id="rate-billing-period"
                                    name="billing_period_in_months"
                                    defaultValue={
                                        rate?.billing_period_in_months ?? 1
                                    }
                                >
                                    {billingPeriods.map((months) => (
                                        <option key={months} value={months}>
                                            {billingFrequencyLabel(months)}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            <div className="flex items-start gap-2">
                                <input
                                    type="hidden"
                                    name="prorates_first_month"
                                    value={prorates ? '1' : '0'}
                                />
                                <Checkbox
                                    id="rate-prorates"
                                    checked={prorates}
                                    onCheckedChange={(checked) =>
                                        setProrates(checked === true)
                                    }
                                />
                                <div className="grid gap-0.5">
                                    <Label
                                        htmlFor="rate-prorates"
                                        className="font-normal"
                                    >
                                        Charge part of the first month
                                    </Label>
                                    <p className="text-xs text-muted-foreground">
                                        A service starting mid-month pays only
                                        for the days left. Turn off for things
                                        sold whole, such as domain names.
                                    </p>
                                </div>
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => onOpenChange(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    disabled={processing}
                                    data-test="save-rate-button"
                                >
                                    {processing && <Spinner />}
                                    {rate ? 'Save rate' : 'Add rate'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
