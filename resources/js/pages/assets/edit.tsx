import { Head, Link, setLayoutProps } from '@inertiajs/react';
import LinkedRecords from '@/components/custom-fields/linked-records';
import { KeyRound } from 'lucide-react';
import ConfirmDelete from '@/components/confirm-delete';
import PageHeader from '@/components/page-header';
import AssetForm from '@/components/assets/asset-form';
import DeleteAssetController from '@/actions/App/Asset/Controllers/DeleteAssetController';
import UpdateAssetController from '@/actions/App/Asset/Controllers/UpdateAssetController';
import {
    index as customersIndex,
    secrets as customerSecrets,
    show as showCustomer,
} from '@/routes/customers';
import type {
    Abilities,
    Asset,
    Customer,
    CustomerOption,
    LinkedRecordGroup,
    Option,
    SecretItem,
} from '@/types';

export default function EditAsset({
    can,
    linkedRecords,
    asset,
    customer,
    statuses,
    locations,
    secrets,
}: {
    secrets: SecretItem[];
    can: Abilities;
    /** What points at this record through record-link custom fields. */
    linkedRecords: LinkedRecordGroup[];
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

                <section className="max-w-2xl space-y-2">
                    <div className="flex items-center justify-between gap-2">
                        <h2 className="font-medium">Secrets</h2>
                        <Link
                            href={customerSecrets(customer.id, {
                                query: { asset: asset.id },
                            })}
                            className="text-sm text-muted-foreground hover:text-foreground"
                        >
                            {secrets.length > 0
                                ? 'Open secrets'
                                : 'Add a secret'}
                        </Link>
                    </div>
                    {secrets.length > 0 ? (
                        <ul className="divide-y rounded-lg border bg-card">
                            {secrets.map((secret) => (
                                <li
                                    key={secret.id}
                                    className="flex h-10 items-center gap-2.5 px-3 text-sm"
                                >
                                    <KeyRound className="size-4 text-subtle-foreground" />
                                    <span className="truncate">
                                        {secret.title}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            No logins or keys saved for this asset.
                        </p>
                    )}
                </section>
                <div className="max-w-2xl">
                    <LinkedRecords groups={linkedRecords} />
                </div>
            </div>
        </>
    );
}
