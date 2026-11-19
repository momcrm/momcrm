import { Form, Link } from '@inertiajs/react';
import CustomFieldsFormFields from '@/components/custom-fields/custom-fields-form';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import FormField from '@/components/form-field';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatCents } from '@/lib/money';
import type { CustomerOption, Invoice, InvoiceItem, Service } from '@/types';

type Action = { action: string; method: 'post' | 'put' };

type Row = {
    key: number;
    service_id: string;
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
        description: item.description,
        quantity: String(item.quantity),
        unit_price: (item.unit_price_in_cents / 100).toFixed(2),
        rental_starts_on: item.rental_starts_on,
        rental_ends_on: item.rental_ends_on,
    };
}

function rowCents(row: Row): number {
    const quantity = Number(row.quantity) || 0;
    const price = Math.round((Number(row.unit_price) || 0) * 100);

    return quantity * price;
}

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
}: {
    action: Action;
    invoice?: Invoice;
    items?: InvoiceItem[];
    customers?: CustomerOption[];
    services: Service[];
    customerId?: string | null;
    defaults?: {
        issued_on: string;
        due_on: string;
        tax_rate_basis_points: number;
    };
    submitLabel: string;
    cancelHref: string;
}) {
    const [selectedCustomer, setSelectedCustomer] = useState(
        invoice?.customer_id ?? customerId ?? '',
    );
    const [rows, setRows] = useState<Row[]>(
        items && items.length > 0 ? items.map(toRow) : [emptyRow()],
    );
    const [taxRate, setTaxRate] = useState(
        String(
            (invoice?.tax_rate_basis_points ??
                defaults?.tax_rate_basis_points ??
                1000) / 100,
        ),
    );

    const customerServices = services.filter(
        (service) => service.customer_id === selectedCustomer,
    );

    const updateRow = (key: number, changes: Partial<Row>) =>
        setRows((current) =>
            current.map((row) =>
                row.key === key ? { ...row, ...changes } : row,
            ),
        );

    const pickService = (key: number, serviceId: string) => {
        const service = services.find((option) => option.id === serviceId);

        updateRow(key, {
            service_id: serviceId,
            rental_starts_on: null,
            rental_ends_on: null,
            ...(service && {
                description: service.name,
                quantity: String(service.quantity ?? 1),
                unit_price:
                    service.unit_price_in_cents != null
                        ? (service.unit_price_in_cents / 100).toFixed(2)
                        : '',
            }),
        });
    };

    const subtotal = rows.reduce((sum, row) => sum + rowCents(row), 0);
    const tax = Math.round((subtotal * (Number(taxRate) || 0)) / 100);

    return (
        <Form {...action} className="max-w-3xl space-y-8">
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-6 sm:grid-cols-2">
                        {customers && (
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
                                        setSelectedCustomer(event.target.value)
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
                        )}
                        <FormField
                            id="purchase_order"
                            label="Purchase order"
                            error={errors.purchase_order}
                        >
                            <Input
                                id="purchase_order"
                                name="purchase_order"
                                defaultValue={invoice?.purchase_order ?? ''}
                            />
                        </FormField>
                        <FormField
                            id="issued_on"
                            label="Issued"
                            error={errors.issued_on}
                        >
                            <Input
                                id="issued_on"
                                name="issued_on"
                                type="date"
                                defaultValue={
                                    invoice?.issued_on ?? defaults?.issued_on
                                }
                                required
                            />
                        </FormField>
                        <FormField
                            id="due_on"
                            label="Due"
                            error={errors.due_on}
                        >
                            <Input
                                id="due_on"
                                name="due_on"
                                type="date"
                                defaultValue={
                                    invoice?.due_on ?? defaults?.due_on
                                }
                                required
                            />
                        </FormField>
                    </div>

                    <div className="space-y-3">
                        <h2 className="font-medium">Line items</h2>
                        <div className="space-y-3">
                            {rows.map((row, index) => (
                                <div
                                    key={row.key}
                                    className="grid gap-2 rounded-lg border p-3 sm:grid-cols-[1fr_5rem_7rem_6rem_auto] sm:items-start"
                                >
                                    <div className="grid gap-2">
                                        {row.rental_starts_on &&
                                            row.rental_ends_on && (
                                                <>
                                                    <input
                                                        type="hidden"
                                                        name={`items[${index}][rental_starts_on]`}
                                                        value={
                                                            row.rental_starts_on
                                                        }
                                                    />
                                                    <input
                                                        type="hidden"
                                                        name={`items[${index}][rental_ends_on]`}
                                                        value={
                                                            row.rental_ends_on
                                                        }
                                                    />
                                                </>
                                            )}
                                        {customerServices.length > 0 && (
                                            <NativeSelect
                                                aria-label="Fill from a service"
                                                name={`items[${index}][service_id]`}
                                                value={row.service_id}
                                                onChange={(event) =>
                                                    pickService(
                                                        row.key,
                                                        event.target.value,
                                                    )
                                                }
                                            >
                                                <option value="">
                                                    Custom line
                                                </option>
                                                {customerServices.map(
                                                    (service) => (
                                                        <option
                                                            key={service.id}
                                                            value={service.id}
                                                        >
                                                            {service.name}
                                                        </option>
                                                    ),
                                                )}
                                            </NativeSelect>
                                        )}
                                        <Input
                                            aria-label="Description"
                                            placeholder="Description"
                                            name={`items[${index}][description]`}
                                            value={row.description}
                                            onChange={(event) =>
                                                updateRow(row.key, {
                                                    description:
                                                        event.target.value,
                                                })
                                            }
                                            required
                                        />
                                        <InputError
                                            message={
                                                errors[
                                                    `items.${index}.description`
                                                ] ??
                                                errors[
                                                    `items.${index}.unit_price_in_cents`
                                                ] ??
                                                errors[
                                                    `items.${index}.quantity`
                                                ]
                                            }
                                        />
                                    </div>
                                    <Input
                                        aria-label="Quantity"
                                        type="number"
                                        min="1"
                                        name={`items[${index}][quantity]`}
                                        value={row.quantity}
                                        onChange={(event) =>
                                            updateRow(row.key, {
                                                quantity: event.target.value,
                                            })
                                        }
                                        required
                                    />
                                    <Input
                                        aria-label="Price each"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        inputMode="decimal"
                                        placeholder="0.00"
                                        name={`items[${index}][unit_price]`}
                                        value={row.unit_price}
                                        onChange={(event) =>
                                            updateRow(row.key, {
                                                unit_price: event.target.value,
                                            })
                                        }
                                        required
                                    />
                                    <p className="py-2 text-right text-sm tabular-nums">
                                        {formatCents(rowCents(row))}
                                    </p>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove line"
                                        disabled={rows.length === 1}
                                        onClick={() =>
                                            setRows((current) =>
                                                current.filter(
                                                    (other) =>
                                                        other.key !== row.key,
                                                ),
                                            )
                                        }
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            ))}
                        </div>
                        <InputError message={errors.items} />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                setRows((current) => [...current, emptyRow()])
                            }
                        >
                            Add line
                        </Button>
                    </div>

                    <div className="ml-auto grid max-w-xs gap-2 text-sm">
                        <div className="flex justify-between">
                            <span>Subtotal</span>
                            <span className="tabular-nums">
                                {formatCents(subtotal)}
                            </span>
                        </div>
                        <div className="flex items-center justify-between gap-2">
                            <label htmlFor="tax_rate">Tax (%)</label>
                            <Input
                                id="tax_rate"
                                name="tax_rate"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                className="w-20"
                                value={taxRate}
                                onChange={(event) =>
                                    setTaxRate(event.target.value)
                                }
                            />
                            <span className="tabular-nums">
                                {formatCents(tax)}
                            </span>
                        </div>
                        <InputError message={errors.tax_rate_basis_points} />
                        <div className="flex justify-between border-t pt-2 font-medium">
                            <span>Total (AUD)</span>
                            <span className="tabular-nums">
                                {formatCents(subtotal + tax)}
                            </span>
                        </div>
                    </div>

                    <div className="grid gap-6 empty:hidden sm:grid-cols-2">
                        <CustomFieldsFormFields
                            target="invoice"
                            values={invoice?.custom_fields}
                            errors={errors}
                        />
                    </div>

                    <FormField
                        id="notes"
                        label="Notes"
                        hint="Shown on the invoice, for example payment details."
                        error={errors.notes}
                    >
                        <Textarea
                            id="notes"
                            name="notes"
                            rows={3}
                            defaultValue={invoice?.notes ?? ''}
                        />
                    </FormField>

                    <div className="flex items-center gap-2">
                        <Button
                            disabled={processing}
                            data-test="save-invoice-button"
                        >
                            {processing && <Spinner />}
                            {submitLabel}
                        </Button>
                        <Button variant="ghost" asChild>
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
