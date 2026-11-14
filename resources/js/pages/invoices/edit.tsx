import { Head, setLayoutProps } from '@inertiajs/react';
import InvoiceForm from '@/components/invoices/invoice-form';
import PageHeader from '@/components/page-header';
import UpdateInvoiceController from '@/actions/App/Invoice/Controllers/UpdateInvoiceController';
import { edit, index, show } from '@/routes/invoices';
import type { Customer, Invoice, InvoiceItem, Service } from '@/types';

export default function EditInvoice({
    invoice,
    items,
    customer,
    services,
}: {
    invoice: Invoice;
    items: InvoiceItem[];
    customer: Customer;
    services: Service[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Invoices', href: index() },
            { title: invoice.reference, href: show(invoice.id) },
            { title: 'Edit', href: edit(invoice.id) },
        ],
    });

    return (
        <>
            <Head title={`Edit ${invoice.reference}`} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={`Edit ${invoice.reference}`}
                    description={`For ${customer.name}`}
                />
                <InvoiceForm
                    action={UpdateInvoiceController.form(invoice.id)}
                    invoice={invoice}
                    items={items}
                    services={services}
                    submitLabel="Save draft"
                    cancelHref={show(invoice.id).url}
                />
            </div>
        </>
    );
}
