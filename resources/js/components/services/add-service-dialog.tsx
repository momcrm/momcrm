import StoreServiceController from '@/actions/App/Service/Controllers/StoreServiceController';
import ServiceForm from '@/components/services/service-form';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import type { CustomerOption, ServiceFormOptions } from '@/types';

/**
 * Adds a service to a customer without leaving their Services tab.
 */
export default function AddServiceDialog({
    customer,
    options,
    open,
    onOpenChange,
}: {
    customer: CustomerOption;
    options: ServiceFormOptions;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogTitle>Add service</DialogTitle>
                <DialogDescription>
                    What {customer.name} gets from you, priced from one of your
                    rates.
                </DialogDescription>
                {open && (
                    <ServiceForm
                        action={StoreServiceController.form(customer.id)}
                        options={options}
                        submitLabel="Add service"
                        className="mt-2"
                        onCancel={() => onOpenChange(false)}
                        onSuccess={() => onOpenChange(false)}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}
