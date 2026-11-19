import { Head } from '@inertiajs/react';
import UpdateServiceController from '@/actions/App/Service/Controllers/UpdateServiceController';
import { TabHeader } from '@/components/customers/workspace-tab';
import ServiceForm from '@/components/services/service-form';
import {
    ChangeRateSection,
    ServiceLifecycleSections,
} from '@/components/services/service-manage';
import { useServiceBreadcrumbs } from '@/layouts/service/layout';
import { edit } from '@/routes/services';
import type { ServiceFormOptions, ServiceWorkspace } from '@/types';

export default function ServiceSettings({
    workspace,
    ...options
}: ServiceFormOptions & {
    workspace: ServiceWorkspace;
}) {
    const { service, can } = workspace;
    useServiceBreadcrumbs(workspace, {
        title: 'Settings',
        href: edit(service.id),
    });

    return (
        <>
            <Head title={`${service.name} · Settings`} />
            <TabHeader
                title="Settings"
                description="The service's details, its rate, and turning it on, off or away."
            />
            <div className="flex flex-col gap-8">
                <ServiceForm
                    action={UpdateServiceController.form(service.id)}
                    service={service}
                    options={options}
                    submitLabel="Save"
                />
                <div className="flex max-w-2xl flex-col gap-6">
                    {can.update && (
                        <ChangeRateSection
                            service={service}
                            rates={options.rates}
                        />
                    )}
                    <ServiceLifecycleSections service={service} can={can} />
                </div>
            </div>
        </>
    );
}
