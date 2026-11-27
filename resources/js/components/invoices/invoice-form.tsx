import { Form, Link } from '@inertiajs/react';
import { Check, ChevronDown, Plus, Repeat, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import CustomFieldsFormFields from '@/components/custom-fields/custom-fields-form';
import FormField from '@/components/form-field';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { DEFAULT_CURRENCY, formatCents } from '@/lib/money';
import { cn } from '@/lib/utils';
import type { CustomerOption, Invoice, InvoiceItem, Service } from '@/types';

type Action = { action: string; method: 'post' | 'put' };

type Row = {
    key: number;
    service_id: string;
    /** The one-off charge the line bills, kept while the line is. */
    charge_id: string | null;
    description: string;
    quantity: string;
    unit_price: string;
    /** The days the line bills its service for, kept while the service is. */
    rental_starts_on: string | null;
    rental_ends_on: string | null;
};

let nextKey = 0;

function emptyRow(): Row {
    return {
        key: nextKey++,
        service_id: '',
        charge_id: null,
        description: '',
        quantity: '1',
        unit_price: '',
        rental_starts_on: null,
        rental_ends_on: null,
    };
}

function toRow(item: InvoiceItem): Row {
    return {
        key: nextKey++,
        service_id: item.service_id ?? '',
        charge_id: item.charge_id,
        description: item.description,
        quantity: String(item.quantity),
        unit_price: (item.unit_price_in_cents / 100).toFixed(2),
        rental_starts_on: item.rental_starts_on,
        rental_ends_on: item.rental_ends_on,
    };
}

function serviceRow(service: Service): Row {
    return {
        ...emptyRow(),
        service_id: service.id,
        description: service.name,
        quantity: String(service.quantity ?? 1),
        unit_price:
            service.unit_price_in_cents != null
                ? (service.unit_price_in_cents / 100).toFixed(2)
                : '',
    };
}

function rowCents(row: Row): number {
    const quantity = Number(row.quantity) || 0;
    const price = Math.round((Number(row.unit_price) || 0) * 100);

    return quantity * price;
}

/** Nothing has been typed into the line yet. */
function isBlank(row: Row): boolean {
    return (
        row.service_id === '' &&
        row.charge_id === null &&
        row.description.trim() === '' &&
        row.unit_price === ''
    );
}

const periodFormatter = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

function formatPeriod(startsOn: string, endsOn: string): string {
    return periodFormatter.formatRange(
        new Date(`${startsOn}T00:00:00`),
        new Date(`${endsOn}T00:00:00`),
    );
}

/** The columns of a line on a wide screen: what, how many, each, amount, remove. */
const LINE_COLUMNS =
    'sm:grid-cols-[minmax(0,1fr)_4.5rem_7rem_7rem_1.625rem] sm:gap-x-2';

const eyebrowClass =
    'text-[10px] font-semibold tracking-[0.14em] text-subtle-foreground uppercase';

/**
 * The fields of an invoice: who it bills and when it's due, its lines and
 * what they add up to. On a page it lays itself out down the page; in a
 * dialog its fields scroll between the dialog's title and a bar that keeps
 * the total and the save button in view.
 */
export default function InvoiceForm({
    action,
    invoice,
    items,
    customers,
    services,
    customerId,
    defaults,
    submitLabel,
    cancelHref,
    onCancel,
    onSuccess,
    layout = 'page',
}: {
    action: Action;
    invoice?: Invoice;
    items?: InvoiceItem[];
    /** Customers to choose from, with the currency each is billed in and the days they have to pay. */
    customers?: (CustomerOption & { currency: string; days_to_pay: number })[];
    services: Service[];
    customerId?: string | null;
    defaults?: {
        issued_on: string;
        due_on: string;
        tax_rate_basis_points: number;
    };
    submitLabel: string;
    /** Where Cancel goes, on a page. */
    cancelHref?: string;
    /** What Cancel does, in a dialog. */
    onCancel?: () => void;
    onSuccess?: () => void;
    layout?: 'page' | 'dialog';
}) {
    const inDialog = layout === 'dialog';
    const [selectedCustomer, setSelectedCustomer] = useState(
        invoice?.customer_id ?? customerId ?? '',
    );
    const [issuedOn, setIssuedOn] = useState(
        invoice?.issued_on ?? defaults?.issued_on ?? '',
    );
    const [dueOn, setDueOn] = useState(
        invoice?.due_on ?? defaults?.due_on ?? '',
    );

    /**
     * A new invoice is due by its customer's payment terms, counted from
     * the day it is issued, until the due date is changed by hand.
     */
    const [isDueOnTyped, setIsDueOnTyped] = useState(invoice !== undefined);
    const daysToPay = customers?.find(
        (customer) => customer.id === selectedCustomer,
    )?.days_to_pay;
    const followTerms = (customerId: string, issued: string) => {
        const days = customers?.find(
            (customer) => customer.id === customerId,
        )?.days_to_pay;

        if (isDueOnTyped || days === undefined || issued === '') {
            return;
        }

        const due = new Date(`${issued}T00:00:00`);
        due.setDate(due.getDate() + days);
        setDueOn(
            `${due.getFullYear()}-${String(due.getMonth() + 1).padStart(2, '0')}-${String(due.getDate()).padStart(2, '0')}`,
        );
    };
    const [rows, setRows] = useState<Row[]>(
        items && items.length > 0 ? items.map(toRow) : [emptyRow()],
    );

    /**
     * The line to put the cursor in: a line just added, or the first line of
     * a dialog that opened with its customer already chosen.
     */
    const [focusedRow, setFocusedRow] = useState<number | null>(
        inDialog && selectedCustomer !== '' ? rows[0].key : null,
    );
    const addRow = () => {
        const row = emptyRow();

        setRows((current) => [...current, row]);
        setFocusedRow(row.key);
    };
    const [taxRate, setTaxRate] = useState(
        String(
            (invoice?.tax_rate_basis_points ??
                defaults?.tax_rate_basis_points ??
                1000) / 100,
        ),
    );

    const currency =
        invoice?.currency ??
        customers?.find((customer) => customer.id === selectedCustomer)
            ?.currency ??
        DEFAULT_CURRENCY;

    const customerServices = services.filter(
        (service) => service.customer_id === selectedCustomer,
    );

    const updateRow = (key: number, changes: Partial<Row>) =>
        setRows((current) =>
            current.map((row) =>
                row.key === key ? { ...row, ...changes } : row,
            ),
        );

    /**
     * A line for a service, filled with what it's billed at. It takes the
     * place of a single line nothing was typed into.
     */
    const addService = (service: Service) =>
        setRows((current) =>
            current.length === 1 && isBlank(current[0])
                ? [serviceRow(service)]
                : [...current, serviceRow(service)],
        );

    /** Another customer's services can't be billed to this one. */
    const chooseCustomer = (id: string) => {
        setSelectedCustomer(id);
        setRows((current) =>
            current.map((row) =>
                row.service_id === ''
                    ? row
                    : {
                          ...row,
                          service_id: '',
                          rental_starts_on: null,
                          rental_ends_on: null,
                      },
            ),
        );
        followTerms(id, issuedOn);
    };

    const subtotal = rows.reduce((sum, row) => sum + rowCents(row), 0);
    const tax = Math.round((subtotal * (Number(taxRate) || 0)) / 100);
    const total = formatCents(subtotal + tax, currency);

    return (
        <Form
            {...action}
            options={{ preserveScroll: true }}
            onSuccess={onSuccess}
            className={
                inDialog
                    ? 'flex min-h-0 flex-1 flex-col'
                    : 'max-w-3xl space-y-8'
            }
        >
            {({ processing, errors }) => (
                <>
                    <div
                        className={cn(
                            'flex flex-col gap-7',
                            inDialog &&
                                'min-h-0 flex-1 overflow-y-auto px-5 py-5',
                        )}
                    >
                        <div className="grid gap-x-4 gap-y-5 sm:grid-cols-6">
                            {customers && (
                                <div className="sm:col-span-3">
                                    <FormField
                                        id="customer_id"
                                        label="Customer"
                                        error={errors.customer_id}
                                    >
                                        <NativeSelect
                                            id="customer_id"
                                            name="customer_id"
                                            value={selectedCustomer}
                                            onChange={(event) =>
                                                chooseCustomer(
                                                    event.target.value,
                                                )
                                            }
                                            autoFocus={
                                                inDialog &&
                                                selectedCustomer === ''
                                            }
                                            required
                                        >
                                            <option value="" disabled>
                                                Choose a customer
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
                                </div>
                            )}
                            <div
                                className={
                                    customers
                                        ? 'sm:col-span-3'
                                        : 'sm:col-span-2'
                                }
                            >
                                <FormField
                                    id="purchase_order"
                                    label="Purchase order"
                                    error={errors.purchase_order}
                                >
                                    <Input
                                        id="purchase_order"
                                        name="purchase_order"
                                        placeholder="Optional"
                                        defaultValue={
                                            invoice?.purchase_order ?? ''
                                        }
                                    />
                                </FormField>
                            </div>
                            <div
                                className={
                                    customers
                                        ? 'sm:col-span-3'
                                        : 'sm:col-span-2'
                                }
                            >
                                <FormField
                                    id="issued_on"
                                    label="Issued"
                                    error={errors.issued_on}
                                >
                                    <Input
                                        id="issued_on"
                                        name="issued_on"
                                        type="date"
                                        value={issuedOn}
                                        onChange={(event) => {
                                            setIssuedOn(event.target.value);
                                            followTerms(
                                                selectedCustomer,
                                                event.target.value,
                                            );
                                        }}
                                        required
                                    />
                                </FormField>
                            </div>
                            <div
                                className={
                                    customers
                                        ? 'sm:col-span-3'
                                        : 'sm:col-span-2'
                                }
                            >
                                <FormField
                                    id="due_on"
                                    label="Due"
                                    hint={
                                        !isDueOnTyped && daysToPay !== undefined
                                            ? daysToPay === 0
                                                ? "The day it's issued, by their payment terms."
                                                : `${daysToPay} ${daysToPay === 1 ? 'day' : 'days'} after it's issued, by their payment terms.`
                                            : undefined
                                    }
                                    error={errors.due_on}
                                >
                                    <Input
                                        id="due_on"
                                        name="due_on"
                                        type="date"
                                        value={dueOn}
                                        onChange={(event) => {
                                            setDueOn(event.target.value);
                                            setIsDueOnTyped(true);
                                        }}
                                        required
                                    />
                                </FormField>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <div className="overflow-hidden rounded-lg border">
                                <div
                                    aria-hidden
                                    className={cn(
                                        'hidden border-b bg-muted/50 px-3 py-2 sm:grid',
                                        LINE_COLUMNS,
                                        eyebrowClass,
                                    )}
                                >
                                    <div>Description</div>
                                    <div className="text-right">Qty</div>
                                    <div className="text-right">Price</div>
                                    <div className="text-right">Amount</div>
                                </div>

                                <div className="divide-y">
                                    {rows.map((row, index) => (
                                        <LineRow
                                            key={row.key}
                                            row={row}
                                            index={index}
                                            currency={currency}
                                            service={services.find(
                                                (service) =>
                                                    service.id ===
                                                    row.service_id,
                                            )}
                                            error={
                                                errors[
                                                    `items.${index}.description`
                                                ] ??
                                                errors[
                                                    `items.${index}.unit_price_in_cents`
                                                ] ??
                                                errors[
                                                    `items.${index}.quantity`
                                                ] ??
                                                errors[
                                                    `items.${index}.service_id`
                                                ] ??
                                                errors[
                                                    `items.${index}.charge_id`
                                                ]
                                            }
                                            canRemove={rows.length > 1}
                                            autoFocus={row.key === focusedRow}
                                            onChange={(changes) =>
                                                updateRow(row.key, changes)
                                            }
                                            onRemove={() =>
                                                setRows((current) =>
                                                    current.filter(
                                                        (other) =>
                                                            other.key !==
                                                            row.key,
                                                    ),
                                                )
                                            }
                                        />
                                    ))}
                                </div>

                                <div className="flex flex-wrap items-center gap-1 border-t bg-muted/30 px-2 py-1.5">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={addRow}
                                    >
                                        <Plus />
                                        Add line
                                    </Button>
                                    {customerServices.length > 0 && (
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <Repeat />
                                                    Add a service
                                                    <ChevronDown className="size-3.5! text-muted-foreground" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent
                                                align="start"
                                                className="max-h-72 w-80 overflow-y-auto"
                                            >
                                                <DropdownMenuLabel className="text-[11px] font-medium tracking-wide text-subtle-foreground uppercase">
                                                    Their services
                                                </DropdownMenuLabel>
                                                {customerServices.map(
                                                    (service) => (
                                                        <DropdownMenuItem
                                                            key={service.id}
                                                            onSelect={() =>
                                                                addService(
                                                                    service,
                                                                )
                                                            }
                                                        >
                                                            <span className="min-w-0 flex-1 truncate">
                                                                {service.name}
                                                            </span>
                                                            {rows.some(
                                                                (row) =>
                                                                    row.service_id ===
                                                                    service.id,
                                                            ) && (
                                                                <>
                                                                    <Check
                                                                        aria-hidden
                                                                        className="text-muted-foreground"
                                                                    />
                                                                    <span className="sr-only">
                                                                        On this
                                                                        invoice
                                                                    </span>
                                                                </>
                                                            )}
                                                            {service.unit_price_in_cents !=
                                                                null && (
                                                                <span className="text-muted-foreground tabular-nums">
                                                                    {formatCents(
                                                                        service.unit_price_in_cents *
                                                                            (service.quantity ??
                                                                                1),
                                                                        currency,
                                                                    )}
                                                                </span>
                                                            )}
                                                        </DropdownMenuItem>
                                                    ),
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    )}
                                </div>
                            </div>
                            <InputError message={errors.items} />
                        </div>

                        <div className="grid gap-x-6 gap-y-5 sm:grid-cols-[minmax(0,1fr)_17rem]">
                            <FormField
                                id="notes"
                                label="Notes"
                                hint="Shown on the invoice, for example payment details."
                                error={errors.notes}
                            >
                                <Textarea
                                    id="notes"
                                    name="notes"
                                    rows={4}
                                    className="min-h-24"
                                    defaultValue={invoice?.notes ?? ''}
                                />
                            </FormField>

                            <div className="self-start rounded-lg bg-muted/60 p-4 text-sm">
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between gap-4">
                                        <span className="text-muted-foreground">
                                            Subtotal
                                        </span>
                                        <span className="font-medium tabular-nums">
                                            {formatCents(subtotal, currency)}
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between gap-4">
                                        <label
                                            htmlFor="tax_rate"
                                            className="flex items-center gap-2 text-muted-foreground"
                                        >
                                            Tax
                                            <span className="relative">
                                                <Input
                                                    id="tax_rate"
                                                    name="tax_rate"
                                                    type="number"
                                                    min="0"
                                                    max="100"
                                                    step="0.01"
                                                    inputMode="decimal"
                                                    className="h-control-sm w-16 [appearance:textfield] pr-6 text-right text-foreground tabular-nums [&::-webkit-inner-spin-button]:appearance-none"
                                                    value={taxRate}
                                                    onChange={(event) =>
                                                        setTaxRate(
                                                            event.target.value,
                                                        )
                                                    }
                                                />
                                                <span
                                                    aria-hidden
                                                    className="pointer-events-none absolute inset-y-0 right-2 flex items-center text-xs"
                                                >
                                                    %
                                                </span>
                                            </span>
                                        </label>
                                        <span className="font-medium tabular-nums">
                                            {formatCents(tax, currency)}
                                        </span>
                                    </div>
                                    <InputError
                                        message={errors.tax_rate_basis_points}
                                    />
                                </div>
                                <div className="mt-3 flex items-end justify-between gap-4 border-t pt-3">
                                    <div className={cn(eyebrowClass, 'pb-1')}>
                                        Total · {currency}
                                    </div>
                                    <div className="text-2xl font-semibold tracking-tight tabular-nums">
                                        {total}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-x-4 gap-y-5 empty:hidden sm:grid-cols-2">
                            <CustomFieldsFormFields
                                target="invoice"
                                values={invoice?.custom_fields}
                                errors={errors}
                            />
                        </div>
                    </div>

                    {inDialog ? (
                        <div className="flex shrink-0 flex-wrap items-center gap-2 border-t px-5 py-3">
                            <div className="mr-auto flex items-baseline gap-2 text-sm">
                                <span className="text-muted-foreground">
                                    Total
                                </span>
                                <span className="font-semibold tabular-nums">
                                    {total}
                                </span>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={onCancel}
                            >
                                Cancel
                            </Button>
                            <Button
                                disabled={processing}
                                data-test="save-invoice-button"
                            >
                                {processing && <Spinner />}
                                {submitLabel}
                            </Button>
                        </div>
                    ) : (
                        <div className="flex items-center gap-2">
                            <Button
                                disabled={processing}
                                data-test="save-invoice-button"
                            >
                                {processing && <Spinner />}
                                {submitLabel}
                            </Button>
                            {cancelHref && (
                                <Button variant="ghost" asChild>
                                    <Link href={cancelHref}>Cancel</Link>
                                </Button>
                            )}
                        </div>
                    )}
                </>
            )}
        </Form>
    );
}

const numberInputClass =
    '[appearance:textfield] text-right tabular-nums [&::-webkit-inner-spin-button]:appearance-none';

/**
 * One line of the invoice. A line for a service says which, and for what
 * days when it bills a period; taking the service off leaves the line as
 * typed.
 */
function LineRow({
    row,
    index,
    currency,
    service,
    error,
    canRemove,
    autoFocus,
    onChange,
    onRemove,
}: {
    row: Row;
    index: number;
    currency: string;
    service: Service | undefined;
    error: string | undefined;
    canRemove: boolean;
    /** Put the cursor in the line's description when it appears. */
    autoFocus: boolean;
    onChange: (changes: Partial<Row>) => void;
    onRemove: () => void;
}) {
    const name = (field: string) => `items[${index}][${field}]`;
    const hasPeriod =
        row.service_id !== '' &&
        row.rental_starts_on !== null &&
        row.rental_ends_on !== null;

    return (
        <div
            className={cn(
                'grid grid-cols-[4.5rem_minmax(0,1fr)_auto_1.625rem] items-start gap-2 px-3 py-2.5',
                LINE_COLUMNS,
            )}
        >
            {row.service_id !== '' && (
                <input
                    type="hidden"
                    name={name('service_id')}
                    value={row.service_id}
                />
            )}
            {row.charge_id && (
                <input
                    type="hidden"
                    name={name('charge_id')}
                    value={row.charge_id}
                />
            )}
            {hasPeriod && (
                <>
                    <input
                        type="hidden"
                        name={name('rental_starts_on')}
                        value={row.rental_starts_on ?? ''}
                    />
                    <input
                        type="hidden"
                        name={name('rental_ends_on')}
                        value={row.rental_ends_on ?? ''}
                    />
                </>
            )}

            <div className="col-span-full grid min-w-0 gap-1.5 sm:col-span-1">
                <Input
                    aria-label="Description"
                    placeholder="What you're billing for"
                    autoFocus={autoFocus}
                    name={name('description')}
                    value={row.description}
                    onChange={(event) =>
                        onChange({ description: event.target.value })
                    }
                    required
                />
                {row.service_id !== '' && (
                    <div className="flex min-w-0 items-center gap-1.5 text-xs text-muted-foreground">
                        <Repeat className="size-3 shrink-0" />
                        <span className="truncate">
                            Service · {service?.name ?? 'no longer active'}
                            {hasPeriod &&
                                ` · ${formatPeriod(row.rental_starts_on ?? '', row.rental_ends_on ?? '')}`}
                        </span>
                        <button
                            type="button"
                            aria-label="Take the service off this line"
                            title="Take the service off this line"
                            className="inline-flex size-4 shrink-0 items-center justify-center rounded-sm outline-none hover:bg-muted hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            onClick={() =>
                                onChange({
                                    service_id: '',
                                    rental_starts_on: null,
                                    rental_ends_on: null,
                                })
                            }
                        >
                            <X className="size-3" />
                        </button>
                    </div>
                )}
                <InputError message={error} className="text-xs" />
            </div>
            <Input
                aria-label="Quantity"
                type="number"
                min="1"
                name={name('quantity')}
                className={numberInputClass}
                value={row.quantity}
                onChange={(event) => onChange({ quantity: event.target.value })}
                required
            />
            <Input
                aria-label="Price each"
                type="number"
                min="0"
                step="0.01"
                inputMode="decimal"
                placeholder="0.00"
                name={name('unit_price')}
                className={numberInputClass}
                value={row.unit_price}
                onChange={(event) =>
                    onChange({ unit_price: event.target.value })
                }
                required
            />
            <div className="flex h-control-md items-center justify-end text-sm font-medium tabular-nums">
                {formatCents(rowCents(row), currency)}
            </div>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                className="mt-0.5 text-muted-foreground"
                aria-label="Remove line"
                disabled={!canRemove}
                onClick={onRemove}
            >
                <Trash2 className="size-3.5!" />
            </Button>
        </div>
    );
}
