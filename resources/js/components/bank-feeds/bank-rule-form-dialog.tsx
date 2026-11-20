import { Form } from '@inertiajs/react';
import { useState } from 'react';
import FormField from '@/components/form-field';
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
import { store, update } from '@/routes/bank-rules';
import type { BankRule, CustomerOption, Option } from '@/types';

/**
 * Adds a bank feed rule, or changes one: deposits whose description
 * contains its text are from a customer, or are hidden.
 */
export default function BankRuleFormDialog({
    open,
    onOpenChange,
    kinds,
    customers,
    rule,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    kinds: Option[];
    customers: CustomerOption[];
    rule?: BankRule;
}) {
    const [kind, setKind] = useState(rule?.kind ?? 'match');

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogTitle>{rule ? 'Edit rule' : 'New rule'}</DialogTitle>
                <DialogDescription>
                    Applies to deposits imported from now on, and to pending
                    ones when you match again. Rules come before anything worked
                    out from invoice numbers or names.
                </DialogDescription>
                <Form
                    {...(rule ? update.form(rule.id) : store.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <FormField
                                id="bank-rule-contains"
                                label="When the description contains"
                                hint="Not case sensitive."
                                error={errors.contains}
                            >
                                <Input
                                    id="bank-rule-contains"
                                    name="contains"
                                    defaultValue={rule?.contains ?? ''}
                                    placeholder="HARBOUR DENTAL"
                                    required
                                    autoFocus
                                />
                            </FormField>
                            <FormField
                                id="bank-rule-kind"
                                label="Then"
                                error={errors.kind}
                            >
                                <NativeSelect
                                    id="bank-rule-kind"
                                    name="kind"
                                    value={kind}
                                    onChange={(event) =>
                                        setKind(
                                            event.target
                                                .value as BankRule['kind'],
                                        )
                                    }
                                >
                                    {kinds.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            {kind === 'match' && (
                                <FormField
                                    id="bank-rule-customer"
                                    label="Customer"
                                    error={errors.customer_id}
                                >
                                    <NativeSelect
                                        id="bank-rule-customer"
                                        name="customer_id"
                                        defaultValue={rule?.customer_id ?? ''}
                                        required
                                    >
                                        <option value="" disabled>
                                            Choose a customer…
                                        </option>
                                        {customers.map((customer) => (
                                            <option
                                                key={customer.id}
                                                value={customer.id}
                                            >
                                                {customer.name}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                            )}
                            <DialogFooter>
                                <Button disabled={processing}>
                                    {processing && <Spinner />}
                                    {rule ? 'Save' : 'Add rule'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
