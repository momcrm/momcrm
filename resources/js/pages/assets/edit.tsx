import { Head, setLayoutProps } from '@inertiajs/react';
import ConfirmDelete from '@/components/confirm-delete';
import PageHeader from '@/components/page-header';
import AssetForm from '@/components/assets/asset-form';
import DeleteAssetController from '@/actions/App/Asset/Controllers/DeleteAssetController';
import UpdateAssetController from '@/actions/App/Asset/Controllers/UpdateAssetController';
import {
    index as customersIndex,
    show as showCustomer,
} from '@/routes/customers';
import type {
    Abilities,
    Asset,
    Customer,
    CustomerOption,
    Option,
} from '@/types';

export default function EditAsset({
    can,
    asset,
    customer,
    statuses,
    locations,
}: {
    can: Abilities;
    asset: Asset;
    customer: Customer;
    statuses: Option[];
    locations: CustomerOption[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Customers', href: customersIndex() },
            { title: customer.name, href: showCustomer(customer.id) },
            { title: 'Edit asset', href: showCustomer(customer.id) },
        ],
    });

    return (
        <>
            <Head title="Edit asset" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Edit asset"
                    description={`For ${customer.name}`}
                    actions={
                        can.delete && (
                            <ConfirmDelete
                                action={DeleteAssetController.form(asset.id)}
                                title="Delete this asset?"
                                description="This can't be undone."
                            />
                        )
                    }
                />
                <AssetForm
                    action={UpdateAssetController.form(asset.id)}
                    asset={asset}
                    statuses={statuses}
                    locations={locations}
                    submitLabel="Save"
                    cancelHref={showCustomer(customer.id).url}
                />
            </div>
        </>
    );
}
