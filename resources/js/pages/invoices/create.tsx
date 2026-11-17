import { Head } from '@inertiajs/react';
import { useCreateCustomer } from '@/components/customers/create-customer-dialog';
import EmptyState from '@/components/empty-state';
import InvoiceForm from '@/components/invoices/invoice-form';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import StoreInvoiceController from '@/actions/App/Invoice/Controllers/StoreInvoiceController';
import { create, index } from '@/routes/invoices';
import type { CustomerOption, Service } from '@/types';

export default function CreateInvoice({
    customers,
    services,
    customerId,
    defaults,
}: {
    customers: CustomerOption[];
    services: Service[];
    customerId: string | null;
    defaults: {
        issued_on: string;
        due_on: string;
        tax_rate_basis_points: number;
    };
}) {
    return (
        <>
            <Head title="Create invoice" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Create invoice"
                    description="It's saved as a draft until you mark it sent."
                />
                {customers.length === 0 ? (
                    <EmptyState
                        title="Add a customer first"
                        description="Every invoice is for a customer."
                        action={<AddCustomerButton />}
                    />
                ) : (
                    <InvoiceForm
                        action={StoreInvoiceController.form()}
                        customers={customers}
                        services={services}
                        customerId={customerId}
                        defaults={defaults}
                        submitLabel="Save draft"
                        cancelHref={index().url}
                    />
                )}
            </div>
        </>
    );
}

CreateInvoice.layout = {
    breadcrumbs: [
        { title: 'Invoices', href: index() },
        { title: 'Create invoice', href: create() },
    ],
};

function AddCustomerButton() {
    const { openCreateCustomer } = useCreateCustomer();

    return <Button onClick={() => openCreateCustomer()}>Add customer</Button>;
}
