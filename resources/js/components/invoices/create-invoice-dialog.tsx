import { useHttp } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useMemo,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import StoreInvoiceController from '@/actions/App/Invoice/Controllers/StoreInvoiceController';
import { useCreateCustomer } from '@/components/customers/create-customer-dialog';
import InvoiceForm from '@/components/invoices/invoice-form';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import { formOptions } from '@/routes/invoices';
import type { CustomerOption, Service } from '@/types';

type Defaults = {
    /** The customer the invoice starts out for. */
    customerId?: string | null;
};

type InvoiceFormOptions = {
    customers: (CustomerOption & { currency: string; days_to_pay: number })[];
    services: Service[];
    defaults: {
        issued_on: string;
        due_on: string;
        tax_rate_basis_points: number;
    };
};

const CreateInvoiceContext = createContext<{
    openCreateInvoice: (defaults?: Defaults) => void;
} | null>(null);

/**
 * Opens the new-invoice dialog from anywhere inside the app layout,
 * optionally starting it for a customer.
 */
export function useCreateInvoice(): {
    openCreateInvoice: (defaults?: Defaults) => void;
} {
    const context = useContext(CreateInvoiceContext);

    if (!context) {
        throw new Error(
            'useCreateInvoice must be used inside CreateInvoiceProvider.',
        );
    }

    return context;
}

/**
 * Holds the new-invoice dialog. Its choices are fetched each time it opens,
 * so a customer or service added a moment ago is already there and the
 * invoice is due by the terms of the customer it opens for. Saving goes to
 * the new draft.
 */
export function CreateInvoiceProvider({ children }: { children: ReactNode }) {
    const [defaults, setDefaults] = useState<Defaults | null>(null);
    const [options, setOptions] = useState<InvoiceFormOptions | null>(null);
    const [openCount, setOpenCount] = useState(0);
    const http = useHttp<Record<string, never>, InvoiceFormOptions>();

    const openCreateInvoice = useCallback(
        (next: Defaults = {}) => {
            setDefaults(next);
            setOptions(null);
            setOpenCount((count) => count + 1);
            http.cancel();
            void http
                .get(
                    formOptions.url({
                        query: { customer: next.customerId ?? undefined },
                    }),
                )
                .then(setOptions)
                .catch(() => undefined);
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [],
    );

    const context = useMemo(() => ({ openCreateInvoice }), [openCreateInvoice]);
    const close = () => setDefaults(null);

    return (
        <CreateInvoiceContext.Provider value={context}>
            {children}
            <Dialog
                open={defaults !== null}
                onOpenChange={(open) => !open && close()}
            >
                <DialogContent className="flex max-h-[92vh] flex-col gap-0 overflow-hidden p-0 pt-0 sm:max-w-3xl [&>[data-slot=dialog-close-button]]:top-[19.5px] [&>[data-slot=dialog-close-button]]:right-5">
                    <div className="shrink-0 border-b px-5 py-3.5 pr-14">
                        <DialogTitle>New invoice</DialogTitle>
                        <DialogDescription>
                            It's saved as a draft until you mark it sent.
                        </DialogDescription>
                    </div>
                    {defaults !== null &&
                        (options === null ? (
                            <LoadingBody />
                        ) : options.customers.length === 0 ? (
                            <NobodyToInvoice onDone={close} />
                        ) : (
                            <InvoiceForm
                                key={openCount}
                                layout="dialog"
                                action={StoreInvoiceController.form()}
                                customers={options.customers}
                                services={options.services}
                                customerId={defaults.customerId}
                                defaults={options.defaults}
                                submitLabel="Save draft"
                                onCancel={close}
                                onSuccess={close}
                            />
                        ))}
                </DialogContent>
            </Dialog>
        </CreateInvoiceContext.Provider>
    );
}

function LoadingBody() {
    return (
        <div className="space-y-7 px-5 py-5">
            <div className="grid gap-x-4 gap-y-5 sm:grid-cols-2">
                {[0, 1, 2, 3].map((field) => (
                    <div key={field} className="space-y-1.5">
                        <Skeleton className="h-4 w-20" />
                        <Skeleton className="h-control-md w-full" />
                    </div>
                ))}
            </div>
            <Skeleton className="h-32 w-full" />
            <div className="grid gap-6 sm:grid-cols-[minmax(0,1fr)_17rem]">
                <Skeleton className="h-28 w-full" />
                <Skeleton className="h-28 w-full" />
            </div>
        </div>
    );
}

function NobodyToInvoice({ onDone }: { onDone: () => void }) {
    const { openCreateCustomer } = useCreateCustomer();

    return (
        <div className="space-y-3 px-5 py-5 text-sm text-muted-foreground">
            <p>Every invoice is for a customer. Add one first.</p>
            <Button
                size="sm"
                onClick={() => {
                    onDone();
                    openCreateCustomer();
                }}
            >
                Add customer
            </Button>
        </div>
    );
}
