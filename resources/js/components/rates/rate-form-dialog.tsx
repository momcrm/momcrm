import { Form } from '@inertiajs/react';
import { useState } from 'react';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import { billingFrequencyLabel } from '@/components/rates/billing-period';
import RateCombobox from '@/components/rates/rate-combobox';
import RatePricesField from '@/components/rates/rate-prices-field';
import { rateDescendantIds } from '@/components/rates/rate-tree';
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
import type { Option, Rate } from '@/types';

/**
 * Adds a rate to the price list, or edits one. A new rate can start under a
 * parent, as when added from that rate's row.
 */
export default function RateFormDialog({
    open,
    onOpenChange,
    billingPeriods,
    currencies,
    rates,
    rate,
    parentId = null,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    billingPeriods: number[];
    currencies: Option[];
    /** Every rate of the team, in tree order, to choose a parent from. */
    rates: Rate[];
    rate?: Rate;
    parentId?: string | null;
}) {
    const underIt = rate ? rateDescendantIds(rate.id, rates) : new Set();
    const parents = rates.filter(
        (option) =>
            option.id !== rate?.id &&
            !underIt.has(option.id) &&
            (!option.is_archived || option.id === rate?.parent_id),
    );
    const parent = rates.find(
        (option) => option.id === (rate ? rate.parent_id : parentId),
    );
    const [prorates, setProrates] = useState(
        rate?.prorates_first_month ?? true,
    );

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogTitle>
                    {rate
                        ? `Edit ${rate.name}`
                        : parent
                          ? `New rate under ${parent.name}`
                          : 'New rate'}
                </DialogTitle>
                <DialogDescription>
                    Services are priced from a rate: its monthly price in the
                    customer's currency, times the quantity, times the months
                    billed at once.
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
                                id="rate-parent"
                                label="Under"
                                hint="Groups it in the price list. Both rates can still be sold."
                                error={errors.parent_id}
                            >
                                <RateCombobox
                                    id="rate-parent"
                                    name="parent_id"
                                    rates={parents}
                                    defaultValue={
                                        (rate ? rate.parent_id : parentId) ?? ''
                                    }
                                    emptyLabel="Nothing, at the top"
                                    invalid={Boolean(errors.parent_id)}
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
                            <RatePricesField
                                prices={rate?.prices}
                                currencies={currencies}
                                errors={errors}
                            />
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
