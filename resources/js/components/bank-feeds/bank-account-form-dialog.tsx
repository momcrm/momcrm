import { Form } from '@inertiajs/react';
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
import { store, update } from '@/routes/bank-accounts';
import type { BankAccount, Option } from '@/types';

/**
 * Adds a bank account whose statements will be imported, or renames one.
 */
export default function BankAccountFormDialog({
    open,
    onOpenChange,
    currencies,
    kinds,
    account,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    currencies: Option[];
    kinds: Option[];
    account?: BankAccount;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogTitle>
                    {account ? `Edit ${account.name}` : 'New bank account'}
                </DialogTitle>
                <DialogDescription>
                    Statements you import go into an account. Its deposits can
                    pay customers billed in its currency.
                </DialogDescription>
                <Form
                    {...(account ? update.form(account.id) : store.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <FormField
                                id="bank-account-name"
                                label="Name"
                                error={errors.name}
                            >
                                <Input
                                    id="bank-account-name"
                                    name="name"
                                    defaultValue={account?.name ?? ''}
                                    placeholder="CBA business cheque"
                                    required
                                    autoFocus
                                />
                            </FormField>
                            {!account && kinds.length > 1 && (
                                <FormField
                                    id="bank-account-kind"
                                    label="Files"
                                    error={errors.kind}
                                >
                                    <NativeSelect
                                        id="bank-account-kind"
                                        name="kind"
                                        defaultValue={kinds[0]?.value}
                                    >
                                        {kinds.map((kind) => (
                                            <option
                                                key={kind.value}
                                                value={kind.value}
                                            >
                                                {kind.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                            )}
                            <FormField
                                id="bank-account-currency"
                                label="Currency"
                                hint={
                                    account
                                        ? 'Can’t change once statements are imported.'
                                        : undefined
                                }
                                error={errors.currency}
                            >
                                <NativeSelect
                                    id="bank-account-currency"
                                    name="currency"
                                    defaultValue={
                                        account?.currency ??
                                        currencies[0]?.value
                                    }
                                >
                                    {currencies.map((currency) => (
                                        <option
                                            key={currency.value}
                                            value={currency.value}
                                        >
                                            {currency.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            <DialogFooter>
                                <Button disabled={processing}>
                                    {processing && <Spinner />}
                                    {account ? 'Save' : 'Add account'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
