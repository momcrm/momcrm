import { useHttp } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useMemo,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import ContactForm from '@/components/contacts/contact-form';
import { useCreateCustomer } from '@/components/customers/create-customer-dialog';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import StoreContactController from '@/actions/App/Contact/Controllers/StoreContactController';
import { formOptions } from '@/routes/contacts';
import type { CustomerOption } from '@/types';

type Defaults = { customerId?: string | null };

type ContactFormOptions = { customers: CustomerOption[] };

const CreateContactContext = createContext<{
    openCreateContact: (defaults?: Defaults) => void;
} | null>(null);

/**
 * Opens the new-contact dialog from anywhere inside the app layout,
 * optionally with a customer already chosen.
 */
export function useCreateContact(): {
    openCreateContact: (defaults?: Defaults) => void;
} {
    const context = useContext(CreateContactContext);

    if (!context) {
        throw new Error(
            'useCreateContact must be used inside CreateContactProvider.',
        );
    }

    return context;
}

/**
 * Holds the new-contact dialog. Its customers are fetched each time it opens,
 * so one added a moment ago is already there. Saving goes to that customer's
 * contacts.
 */
export function CreateContactProvider({ children }: { children: ReactNode }) {
    const [defaults, setDefaults] = useState<Defaults | null>(null);
    const [openCount, setOpenCount] = useState(0);
    const [options, setOptions] = useState<ContactFormOptions | null>(null);
    const http = useHttp<Record<string, never>, ContactFormOptions>();

    const openCreateContact = useCallback(
        (next: Defaults = {}) => {
            setDefaults(next);
            setOpenCount((count) => count + 1);
            http.cancel();
            void http
                .get(formOptions.url())
                .then(setOptions)
                .catch(() => undefined);
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [],
    );

    const context = useMemo(() => ({ openCreateContact }), [openCreateContact]);
    const close = () => setDefaults(null);

    return (
        <CreateContactContext.Provider value={context}>
            {children}
            <Dialog
                open={defaults !== null}
                onOpenChange={(open) => !open && close()}
            >
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                    <DialogTitle>New contact</DialogTitle>
                    <DialogDescription>
                        Add the person you deal with at a customer.
                    </DialogDescription>
                    {defaults !== null &&
                        (options === null ? (
                            <LoadingBody />
                        ) : options.customers.length === 0 ? (
                            <NoCustomers onDone={close} />
                        ) : (
                            <ContactForm
                                key={openCount}
                                action={StoreContactController.form()}
                                customers={options.customers}
                                customerId={defaults.customerId}
                                submitLabel="Add contact"
                                onCancel={close}
                                onSuccess={close}
                            />
                        ))}
                </DialogContent>
            </Dialog>
        </CreateContactContext.Provider>
    );
}

function LoadingBody() {
    return (
        <div className="space-y-4">
            <Skeleton className="h-control-md w-full" />
            <div className="grid gap-4 sm:grid-cols-2">
                <Skeleton className="h-control-md w-full" />
                <Skeleton className="h-control-md w-full" />
                <Skeleton className="h-control-md w-full" />
                <Skeleton className="h-control-md w-full" />
            </div>
        </div>
    );
}

function NoCustomers({ onDone }: { onDone: () => void }) {
    const { openCreateCustomer } = useCreateCustomer();

    return (
        <div className="space-y-3 text-sm text-muted-foreground">
            <p>Every contact works for a customer. Add one first.</p>
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
