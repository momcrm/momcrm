import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { useCustomFields } from '@/components/custom-fields/custom-field-input';
import {
    CustomFieldValueView,
    hasCustomFieldValue,
} from '@/components/custom-fields/custom-fields-form';
import LinkedRecords from '@/components/custom-fields/linked-records';
import InvoiceStatusBadge from '@/components/invoices/invoice-status-badge';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import MarkInvoicePaidController from '@/actions/App/Invoice/Controllers/MarkInvoicePaidController';
import MarkInvoiceSentController from '@/actions/App/Invoice/Controllers/MarkInvoiceSentController';
import VoidInvoiceController from '@/actions/App/Invoice/Controllers/VoidInvoiceController';
import { formatCents } from '@/lib/money';
import { show as showCustomer } from '@/routes/customers';
import { edit, index, show } from '@/routes/invoices';
import type {
    Customer,
    Invoice,
    InvoiceItem,
    InvoiceTotals,
    LinkedRecordGroup,
} from '@/types';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

function formatDate(value: string | null): string {
    return value ? dateFormatter.format(new Date(value)) : '—';
}

function StatusAction({
    action,
    label,
    variant = 'outline',
}: {
    action: { action: string; method: 'post' };
    label: string;
    variant?: 'default' | 'outline';
}) {
    return (
        <Form {...action}>
            {({ processing }) => (
                <Button variant={variant} disabled={processing}>
                    {label}
                </Button>
            )}
        </Form>
    );
}

export default function ShowInvoice({
    invoice,
    items,
    totals,
    customer,
    linkedRecords,
}: {
    /** What points at the invoice through record-link custom fields. */
    linkedRecords: LinkedRecordGroup[];
    invoice: Invoice;
    items: InvoiceItem[];
    totals: InvoiceTotals;
    customer: Customer;
}) {
    const shownFields = useCustomFields('invoice').filter((field) =>
        hasCustomFieldValue(field, invoice.custom_fields[field.id]),
    );

    setLayoutProps({
        breadcrumbs: [
            { title: 'Invoices', href: index() },
            { title: invoice.reference, href: show(invoice.id) },
        ],
    });

    return (
        <>
            <Head title={invoice.reference} />

            <div className="flex flex-col gap-8 p-4 md:p-6">
                <PageHeader
                    title={invoice.reference}
                    description={
                        <span className="flex items-center gap-2">
                            <Link
                                href={showCustomer(customer.id)}
                                className="underline-offset-4 hover:underline"
                            >
                                {customer.name}
                            </Link>
                            <InvoiceStatusBadge invoice={invoice} />
                        </span>
                    }
                    actions={
                        <>
                            {invoice.status === 'draft' && (
                                <>
                                    <Button variant="outline" asChild>
                                        <Link href={edit(invoice.id)}>
                                            Edit
                                        </Link>
                                    </Button>
                                    <StatusAction
                                        action={MarkInvoiceSentController.form(
                                            invoice.id,
                                        )}
                                        label="Mark as sent"
                                        variant="default"
                                    />
                                </>
                            )}
                            {invoice.status === 'sent' && (
                                <StatusAction
                                    action={MarkInvoicePaidController.form(
                                        invoice.id,
                                    )}
                                    label="Mark as paid"
                                    variant="default"
                                />
                            )}
                            {(invoice.status === 'draft' ||
                                invoice.status === 'sent') && (
                                <StatusAction
                                    action={VoidInvoiceController.form(
                                        invoice.id,
                                    )}
                                    label="Void"
                                />
                            )}
                        </>
                    }
                />

                <dl className="grid gap-4 rounded-lg border p-4 sm:grid-cols-4">
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Issued
                        </dt>
                        <dd>{formatDate(invoice.issued_on)}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Due</dt>
                        <dd>{formatDate(invoice.due_on)}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Purchase order
                        </dt>
                        <dd>{invoice.purchase_order ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            {invoice.paid_at ? 'Paid' : 'Sent'}
                        </dt>
                        <dd>
                            {formatDate(invoice.paid_at ?? invoice.sent_at)}
                        </dd>
                    </div>
                </dl>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">
                                    Description
                                </th>
                                <th className="px-4 py-2 text-right font-medium">
                                    Qty
                                </th>
                                <th className="px-4 py-2 text-right font-medium">
                                    Price
                                </th>
                                <th className="px-4 py-2 text-right font-medium">
                                    Amount
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {items.map((item) => (
                                <tr key={item.id}>
                                    <td className="px-4 py-2">
                                        {item.description}
                                    </td>
                                    <td className="px-4 py-2 text-right tabular-nums">
                                        {item.quantity}
                                    </td>
                                    <td className="px-4 py-2 text-right tabular-nums">
                                        {formatCents(item.unit_price_in_cents)}
                                    </td>
                                    <td className="px-4 py-2 text-right tabular-nums">
                                        {formatCents(item.line_total_in_cents)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot className="border-t">
                            <tr>
                                <td
                                    colSpan={3}
                                    className="px-4 py-2 text-right"
                                >
                                    Subtotal
                                </td>
                                <td className="px-4 py-2 text-right tabular-nums">
                                    {formatCents(totals.subtotal_in_cents)}
                                </td>
                            </tr>
                            <tr>
                                <td
                                    colSpan={3}
                                    className="px-4 py-2 text-right"
                                >
                                    Tax ({invoice.tax_rate_basis_points / 100}%)
                                </td>
                                <td className="px-4 py-2 text-right tabular-nums">
                                    {formatCents(totals.tax_in_cents)}
                                </td>
                            </tr>
                            <tr className="font-medium">
                                <td
                                    colSpan={3}
                                    className="px-4 py-2 text-right"
                                >
                                    Total (AUD)
                                </td>
                                <td className="px-4 py-2 text-right tabular-nums">
                                    {formatCents(totals.total_in_cents)}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {invoice.notes && (
                    <section className="space-y-2">
                        <h2 className="font-medium">Notes</h2>
                        <p className="text-sm whitespace-pre-line">
                            {invoice.notes}
                        </p>
                    </section>
                )}

                {shownFields.length > 0 && (
                    <section className="space-y-2">
                        <h2 className="font-medium">More details</h2>
                        <dl className="grid gap-3 text-sm sm:grid-cols-2">
                            {shownFields.map((field) => (
                                <div key={field.id} className="min-w-0">
                                    <dt className="text-xs text-muted-foreground">
                                        {field.name}
                                    </dt>
                                    <dd className="truncate">
                                        <CustomFieldValueView
                                            field={field}
                                            value={
                                                invoice.custom_fields[field.id]
                                            }
                                        />
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </section>
                )}

                <LinkedRecords groups={linkedRecords} />
            </div>
        </>
    );
}
