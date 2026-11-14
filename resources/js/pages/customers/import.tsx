import { Head } from '@inertiajs/react';
import CsvUpload from '@/components/customers/csv-upload';
import PageHeader from '@/components/page-header';
import ImportCustomersController from '@/actions/App/Customer/Controllers/ImportCustomersController';
import { importMethod as importRoute, index } from '@/routes/customers';

export default function ImportCustomers({ maxRows }: { maxRows: number }) {
    return (
        <>
            <Head title="Import customers" />

            <div className="flex max-w-2xl flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Import customers"
                    description="Upload a CSV from your spreadsheet or old CRM."
                />
                <CsvUpload
                    action={ImportCustomersController.form()}
                    maxRows={maxRows}
                />
            </div>
        </>
    );
}

ImportCustomers.layout = {
    breadcrumbs: [
        { title: 'Customers', href: index() },
        { title: 'Import', href: importRoute() },
    ],
};
