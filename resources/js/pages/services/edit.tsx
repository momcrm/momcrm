import { Head, setLayoutProps } from '@inertiajs/react';
import UpdateServiceController from '@/actions/App/Service/Controllers/UpdateServiceController';
import LinkedRecords from '@/components/custom-fields/linked-records';
import PageHeader from '@/components/page-header';
import { serviceLifecycleLabels } from '@/components/services/service-detail';
import ServiceForm from '@/components/services/service-form';
import {
    ChangeRateSection,
    ServiceLifecycleSections,
} from '@/components/services/service-manage';
import SoftBadge from '@/components/soft-badge';
import {
    index as customersIndex,
    services as customerServices,
    show as showCustomer,
} from '@/routes/customers';
import type {
    Abilities,
    Customer,
    LinkedRecordGroup,
    Service,
    ServiceFormOptions,
} from '@/types';

export default function EditService({
    can,
    linkedRecords,
    service,
    customer,
    ...options
}: ServiceFormOptions & {
    can: Abilities;
    /** What points at this record through record-link custom fields. */
    linkedRecords: LinkedRecordGroup[];
    service: Service;
    customer: Customer;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Customers', href: customersIndex() },
            { title: customer.name, href: showCustomer(customer.id) },
            { title: 'Services', href: customerServices(customer.id) },
            { title: service.name, href: customerServices(customer.id) },
        ],
    });

    return (
        <>
            <Head title={service.name} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={service.name}
                    description={`For ${customer.name}`}
                    actions={
                        service.lifecycle !== 'active' && (
                            <SoftBadge tone="neutral">
                                {serviceLifecycleLabels[service.lifecycle]}
                            </SoftBadge>
                        )
                    }
                />
                <ServiceForm
                    action={UpdateServiceController.form(service.id)}
                    service={service}
                    options={options}
                    submitLabel="Save"
                    cancelHref={customerServices(customer.id).url}
                />
                <div className="flex max-w-2xl flex-col gap-6">
                    {can.update && (
                        <ChangeRateSection
                            service={service}
                            rates={options.rates}
                        />
                    )}
                    <ServiceLifecycleSections service={service} can={can} />
                    <LinkedRecords groups={linkedRecords} />
                </div>
            </div>
        </>
    );
}
