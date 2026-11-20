import { Head, Link } from '@inertiajs/react';
import CombineBilling from '@/components/billing/combine-billing';
import MultipleBilling from '@/components/billing/multiple-billing';
import ProductBilling from '@/components/billing/product-billing';
import SingleBilling from '@/components/billing/single-billing';
import { TabHeader } from '@/components/customers/workspace-tab';
import InvoiceDialog from '@/components/invoices/invoice-dialog';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { cn } from '@/lib/utils';
import { billing } from '@/routes/customers';
import type {
    Charge,
    CustomerWorkspace,
    Option,
    Product,
    Service,
} from '@/types';

type Page = 'multiple' | 'single' | 'product' | 'combine';

/**
 * A customer's Billing tab, as Amphora's company Billing section: Multiple
 * Service Billing makes an invoice from their services; Single Service
 * Billing and Product Billing add lines that wait; Combine Invoice Items
 * makes an invoice from the waiting lines.
 */
export default function CustomerBilling({
    workspace,
    page,
    pages,
    month,
    services,
    products,
    waiting,
    can,
}: {
    workspace: CustomerWorkspace;
    page: Page;
    pages: Option[];
    month: string;
    services: Service[];
    products: Product[];
    waiting: Charge[];
    can: { invoice: boolean; charge: boolean };
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Billing',
        href: billing({ customer: customer.id }),
    });

    return (
        <>
            <Head title={`${customer.name} · Billing`} />
            <TabHeader title="Billing" />

            <nav className="mb-6 flex w-fit flex-wrap gap-1 rounded-lg bg-muted p-1">
                {pages.map((option) => (
                    <Link
                        key={option.value}
                        href={billing({
                            customer: customer.id,
                            page: option.value,
                        })}
                        preserveScroll
                        className={cn(
                            'rounded-md px-3 py-1 text-sm',
                            page === option.value
                                ? 'bg-background shadow-xs'
                                : 'text-muted-foreground',
                        )}
                    >
                        {option.label}
                        {option.value === 'combine' && waiting.length > 0 && (
                            <span className="ml-1.5 text-xs text-muted-foreground tabular-nums">
                                {waiting.length}
                            </span>
                        )}
                    </Link>
                ))}
            </nav>

            {page === 'multiple' && (
                <MultipleBilling
                    key="multiple"
                    customerId={customer.id}
                    currency={customer.currency}
                    services={services}
                    defaultMonth={month}
                    canInvoice={can.invoice}
                />
            )}
            {page === 'single' && (
                <SingleBilling
                    key="single"
                    customerId={customer.id}
                    currency={customer.currency}
                    services={services}
                    defaultMonth={month}
                    canCharge={can.charge}
                />
            )}
            {page === 'product' && (
                <ProductBilling
                    key="product"
                    customerId={customer.id}
                    currency={customer.currency}
                    products={products}
                    canCharge={can.charge}
                />
            )}
            {page === 'combine' && (
                <CombineBilling
                    key="combine"
                    customerId={customer.id}
                    currency={customer.currency}
                    waiting={waiting}
                    products={products}
                    canInvoice={can.invoice}
                    canCharge={can.charge}
                />
            )}

            <InvoiceDialog />
        </>
    );
}
