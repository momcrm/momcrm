import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import FormField from '@/components/form-field';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
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
import { importMethod } from '@/routes/bank-feeds';
import type {
    BankAccount,
    StatementColumns,
    StatementColumnsRequest,
} from '@/types';

type ImportForm = {
    bank_account_id: string;
    files: File[];
    columns: StatementColumns | null;
};

const DATE_ORDERS: {
    value: NonNullable<StatementColumns['date_order']>;
    label: string;
}[] = [
    { value: 'dmy', label: 'Day first (31/12/2025)' },
    { value: 'mdy', label: 'Month first (12/31/2025)' },
    { value: 'ymd', label: 'Year first (2025-12-31)' },
];

/**
 * Says which column of a CSV holds what, from its first rows, when the file
 * couldn't be read on its own. Kept on the account for the next file.
 */
function ColumnsStep({
    request,
    columns,
    onChange,
    errors,
}: {
    request: StatementColumnsRequest;
    columns: StatementColumns;
    onChange: (columns: StatementColumns) => void;
    errors: Record<string, string | undefined>;
}) {
    const options = request.headings.map((heading, index) => (
        <option key={index} value={index}>
            {heading || `Column ${index + 1}`}
        </option>
    ));
    const isSplit = columns.amount === null;
    const set = (changes: Partial<StatementColumns>) =>
        onChange({ ...columns, ...changes });
    const pick = (value: string) => Number.parseInt(value, 10);

    return (
        <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
                We couldn’t tell which columns of{' '}
                <strong>{request.file}</strong> hold what. Check these, and the
                next file from this account in the same layout imports without
                asking.
            </p>

            <div className="overflow-x-auto rounded-md border">
                <table className="w-full text-xs">
                    <thead className="bg-muted/50">
                        <tr>
                            {request.headings.map((heading, index) => (
                                <th
                                    key={index}
                                    className="px-2 py-1.5 text-left font-medium whitespace-nowrap"
                                >
                                    {heading || `Column ${index + 1}`}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {request.rows.map((row, rowIndex) => (
                            <tr key={rowIndex} className="border-t">
                                {request.headings.map((_, index) => (
                                    <td
                                        key={index}
                                        className="max-w-48 truncate px-2 py-1.5 whitespace-nowrap text-muted-foreground"
                                    >
                                        {row[index] ?? ''}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <FormField
                    id="columns-date"
                    label="Date"
                    error={errors['columns.date']}
                >
                    <NativeSelect
                        id="columns-date"
                        value={columns.date}
                        onChange={(event) =>
                            set({ date: pick(event.target.value) })
                        }
                    >
                        {options}
                    </NativeSelect>
                </FormField>
                <FormField
                    id="columns-date-order"
                    label="Dates are written"
                    error={errors['columns.date_order']}
                >
                    <NativeSelect
                        id="columns-date-order"
                        value={columns.date_order ?? ''}
                        onChange={(event) =>
                            set({
                                date_order: event.target
                                    .value as StatementColumns['date_order'],
                            })
                        }
                    >
                        <option value="" disabled>
                            Choose…
                        </option>
                        {DATE_ORDERS.map((order) => (
                            <option key={order.value} value={order.value}>
                                {order.label}
                            </option>
                        ))}
                    </NativeSelect>
                </FormField>
            </div>

            <fieldset className="space-y-2">
                <legend className="text-sm font-medium">Description</legend>
                <div className="flex flex-wrap gap-x-4 gap-y-2">
                    {request.headings.map((heading, index) => (
                        <Label
                            key={index}
                            className="flex items-center gap-2 font-normal"
                        >
                            <Checkbox
                                checked={columns.description.includes(index)}
                                onCheckedChange={(isOn) =>
                                    set({
                                        description: isOn
                                            ? [
                                                  ...columns.description,
                                                  index,
                                              ].sort((a, b) => a - b)
                                            : columns.description.filter(
                                                  (column) => column !== index,
                                              ),
                                    })
                                }
                            />
                            {heading || `Column ${index + 1}`}
                        </Label>
                    ))}
                </div>
                <InputError message={errors['columns.description']} />
            </fieldset>

            <fieldset className="space-y-3">
                <legend className="text-sm font-medium">Money</legend>
                <NativeSelect
                    aria-label="How the money is written"
                    value={isSplit ? 'split' : 'amount'}
                    onChange={(event) =>
                        set(
                            event.target.value === 'split'
                                ? {
                                      amount: null,
                                      credit: columns.amount ?? 0,
                                      debit: null,
                                  }
                                : {
                                      amount: columns.credit ?? 0,
                                      credit: null,
                                      debit: null,
                                  },
                        )
                    }
                >
                    <option value="amount">
                        One amount column, money out negative
                    </option>
                    <option value="split">
                        Separate credit and debit columns
                    </option>
                </NativeSelect>
                {isSplit ? (
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            id="columns-credit"
                            label="Credit (money in)"
                        >
                            <NativeSelect
                                id="columns-credit"
                                value={columns.credit ?? 0}
                                onChange={(event) =>
                                    set({ credit: pick(event.target.value) })
                                }
                            >
                                {options}
                            </NativeSelect>
                        </FormField>
                        <FormField id="columns-debit" label="Debit (money out)">
                            <NativeSelect
                                id="columns-debit"
                                value={columns.debit ?? ''}
                                onChange={(event) =>
                                    set({
                                        debit:
                                            event.target.value === ''
                                                ? null
                                                : pick(event.target.value),
                                    })
                                }
                            >
                                <option value="">None</option>
                                {options}
                            </NativeSelect>
                        </FormField>
                    </div>
                ) : (
                    <FormField
                        id="columns-amount"
                        label="Amount"
                        error={errors['columns.amount']}
                    >
                        <NativeSelect
                            id="columns-amount"
                            value={columns.amount ?? 0}
                            onChange={(event) =>
                                set({ amount: pick(event.target.value) })
                            }
                        >
                            {options}
                        </NativeSelect>
                    </FormField>
                )}
            </fieldset>
        </div>
    );
}

/**
 * Imports statements exported from online banking into one of the team's
 * bank accounts. Each deposit becomes a line; payments out are skipped.
 */
export default function ImportStatementDialog({
    open,
    onOpenChange,
    accounts,
    maxFiles,
    maxRows,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    accounts: BankAccount[];
    maxFiles: number;
    maxRows: number;
}) {
    const form = useForm<ImportForm>({
        bank_account_id: accounts[0]?.id ?? '',
        files: [],
        columns: null,
    });
    const [request, setRequest] = useState<StatementColumnsRequest | null>(
        null,
    );
    const errors = form.errors as Record<string, string | undefined>;
    const fileError =
        errors.files ??
        Object.entries(errors).find(([key]) => key.startsWith('files.'))?.[1];

    const close = () => {
        onOpenChange(false);
        setRequest(null);
        form.reset();
        form.clearErrors();
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        form.submit(importMethod(), {
            forceFormData: true,
            preserveScroll: true,
            onFlash: (flash) => {
                const asked = flash.statementColumns as
                    | StatementColumnsRequest
                    | undefined;

                if (asked) {
                    setRequest(asked);
                    form.setData('columns', asked.guess);
                }
            },
            onSuccess: (page) => {
                if (
                    !(page.flash as Record<string, unknown> | undefined)
                        ?.statementColumns
                ) {
                    close();
                }
            },
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(isOpen) => (isOpen ? onOpenChange(true) : close())}
        >
            <DialogContent className={request ? 'sm:max-w-2xl' : 'sm:max-w-md'}>
                <DialogTitle>Import a statement</DialogTitle>
                <DialogDescription>
                    Export a statement from your online banking as CSV, OFX or
                    QFX and upload it here. Deposits become lines to match to
                    customers; anything already imported is skipped.
                </DialogDescription>
                <form onSubmit={submit} className="space-y-5">
                    {request && form.data.columns ? (
                        <ColumnsStep
                            request={request}
                            columns={form.data.columns}
                            onChange={(columns) =>
                                form.setData('columns', columns)
                            }
                            errors={errors}
                        />
                    ) : (
                        <>
                            <FormField
                                id="import-account"
                                label="Account"
                                error={errors.bank_account_id}
                            >
                                <NativeSelect
                                    id="import-account"
                                    value={form.data.bank_account_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'bank_account_id',
                                            event.target.value,
                                        )
                                    }
                                >
                                    {accounts.map((account) => (
                                        <option
                                            key={account.id}
                                            value={account.id}
                                        >
                                            {account.name} · {account.currency}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            <div className="grid gap-2">
                                <Label htmlFor="import-files">
                                    Statement files
                                </Label>
                                <Input
                                    id="import-files"
                                    type="file"
                                    multiple
                                    accept=".csv,text/csv,.txt,.ofx,.qfx"
                                    onChange={(event) =>
                                        form.setData(
                                            'files',
                                            Array.from(
                                                event.target.files ?? [],
                                            ),
                                        )
                                    }
                                    required
                                />
                                <p className="text-xs text-muted-foreground">
                                    Up to {maxFiles} files of{' '}
                                    {maxRows.toLocaleString()} rows each. We
                                    read the columns from their headings, and
                                    ask when we can’t.
                                </p>
                                <InputError message={fileError} />
                            </div>
                        </>
                    )}
                    {request && <InputError message={fileError} />}
                    {form.progress && (
                        <progress
                            value={form.progress.percentage}
                            max="100"
                            className="w-full"
                        />
                    )}
                    <DialogFooter className="gap-2">
                        {request && (
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => {
                                    setRequest(null);
                                    form.setData('columns', null);
                                }}
                            >
                                Back
                            </Button>
                        )}
                        <Button
                            disabled={
                                form.processing || form.data.files.length === 0
                            }
                        >
                            {form.processing && <Spinner />}
                            Import
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
