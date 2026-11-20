import { Head, setLayoutProps } from '@inertiajs/react';
import { useCustomFields } from '@/components/custom-fields/custom-field-input';
import {
    CustomFieldValueView,
    hasCustomFieldValue,
} from '@/components/custom-fields/custom-fields-form';
import LinkedRecords from '@/components/custom-fields/linked-records';
import InvoiceActions from '@/components/invoices/invoice-actions';
import InvoiceDocument from '@/components/invoices/invoice-document';
import PageHeader from '@/components/page-header';
import { index, show } from '@/routes/invoices';
import type {
    InvoiceDocument as InvoiceDocumentData,
    LinkedRecordGroup,
} from '@/types';

export default function ShowInvoice({
    linkedRecords,
    ...document
}: InvoiceDocumentData & {
    /** What points at the invoice through record-link custom fields. */
    linkedRecords: LinkedRecordGroup[];
}) {
    const { invoice, customer } = document;
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

            <div className="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={invoice.reference}
                    description={customer.name}
                    actions={
                        <InvoiceActions
                            invoice={invoice}
                            creditInCents={document.customer_credit_in_cents}
                        />
                    }
                />

                <InvoiceDocument document={document} />

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
