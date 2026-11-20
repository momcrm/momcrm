import { Form, useHttp } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useMemo,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import { useCustomerTypes } from '@/components/customers/customer-meta';
import CustomFieldsFormFields from '@/components/custom-fields/custom-fields-form';
import FormField from '@/components/form-field';
import TaxIdInputs from '@/components/tax-id-inputs';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import StoreCustomerController from '@/actions/App/Customer/Controllers/StoreCustomerController';
import { formOptions } from '@/routes/customers';
import type { Option } from '@/types';
import { DEFAULT_CURRENCY } from '@/lib/money';

type CustomerFormOptions = {
    statuses: Option[];
    billingTypes: Option[];
    currencies: Option[];
};

const CreateCustomerContext = createContext<{
    openCreateCustomer: () => void;
} | null>(null);

/**
 * Opens the new-customer dialog from anywhere inside the app layout.
 */
export function useCreateCustomer(): { openCreateCustomer: () => void } {
    const context = useContext(CreateCustomerContext);

    if (!context) {
        throw new Error(
            'useCreateCustomer must be used inside CreateCustomerProvider.',
        );
    }

    return context;
}

/**
 * Holds the new-customer dialog. Saving goes to the new customer's page.
 */
export function CreateCustomerProvider({ children }: { children: ReactNode }) {
    const [open, setOpen] = useState(false);
    const [openCount, setOpenCount] = useState(0);
    const [options, setOptions] = useState<CustomerFormOptions | null>(null);
    const http = useHttp<Record<string, never>, CustomerFormOptions>();

    const openCreateCustomer = useCallback(
        () => {
            setOpen(true);
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

    const context = useMemo(
        () => ({ openCreateCustomer }),
        [openCreateCustomer],
    );

    return (
        <CreateCustomerContext.Provider value={context}>
            {children}
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                    <DialogTitle>New customer</DialogTitle>
                    <DialogDescription>
                        Start with a client you're working with this week.
                    </DialogDescription>
                    {open &&
                        (options === null ? (
                            <LoadingBody />
                        ) : (
                            <CreateCustomerForm
                                key={openCount}
                                options={options}
                                onDone={() => setOpen(false)}
                            />
                        ))}
                </DialogContent>
            </Dialog>
        </CreateCustomerContext.Provider>
    );
}

function LoadingBody() {
    return (
        <div className="space-y-4">
            <Skeleton className="h-control-md w-full" />
            <div className="grid gap-4 sm:grid-cols-2">
                <Skeleton className="h-control-md w-full" />
                <Skeleton className="h-control-md w-full" />
            </div>
            <Skeleton className="h-20 w-full" />
        </div>
    );
}

function CreateCustomerForm({
    options,
    onDone,
}: {
    options: CustomerFormOptions;
    onDone: () => void;
}) {
    const types = useCustomerTypes();

    return (
        <Form
            {...StoreCustomerController.form()}
            options={{ preserveScroll: true }}
            onSuccess={onDone}
            className="space-y-5"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-5 sm:grid-cols-[1fr_8rem]">
                        <FormField
                            id="customer-name"
                            label="Name"
                            error={errors.name}
                        >
                            <Input
                                id="customer-name"
                                name="name"
                                required
                                autoFocus
                            />
                        </FormField>
                        <FormField
                            id="customer-prefix"
                            label="Prefix"
                            error={errors.prefix}
                        >
                            <Input
                                id="customer-prefix"
                                name="prefix"
                                maxLength={8}
                                placeholder="ACME"
                                className="font-mono uppercase"
                            />
                        </FormField>
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <FormField
                            id="customer-type"
                            label="Type"
                            error={errors.type_id}
                        >
                            <NativeSelect
                                id="customer-type"
                                name="type_id"
                                defaultValue=""
                            >
                                <option value="">No type</option>
                                {types.map((type) => (
                                    <option key={type.id} value={type.id}>
                                        {type.name}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                        <FormField
                            id="customer-billing"
                            label="Billing"
                            error={errors.billing_type}
                        >
                            <NativeSelect
                                id="customer-billing"
                                name="billing_type"
                                defaultValue="manual"
                            >
                                {options.billingTypes.map((billingType) => (
                                    <option
                                        key={billingType.value}
                                        value={billingType.value}
                                    >
                                        {billingType.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                        <FormField
                            id="customer-currency"
                            label="Currency"
                            hint="What their services are priced and invoiced in."
                            error={errors.currency}
                        >
                            <NativeSelect
                                id="customer-currency"
                                name="currency"
                                defaultValue={DEFAULT_CURRENCY}
                            >
                                {options.currencies.map((currency) => (
                                    <option
                                        key={currency.value}
                                        value={currency.value}
                                    >
                                        {currency.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                        <FormField
                            id="customer-email"
                            label="Email"
                            error={errors.email}
                        >
                            <Input
                                id="customer-email"
                                name="email"
                                type="email"
                            />
                        </FormField>
                        <FormField
                            id="customer-phone"
                            label="Phone"
                            error={errors.phone}
                        >
                            <Input
                                id="customer-phone"
                                name="phone"
                                type="tel"
                            />
                        </FormField>
                        <FormField
                            id="customer-website"
                            label="Website"
                            error={errors.website}
                        >
                            <Input
                                id="customer-website"
                                name="website"
                                type="url"
                                placeholder="https://"
                            />
                        </FormField>
                        <FormField
                            id="customer-tax-id"
                            label="Tax ID"
                            error={errors.tax_id_label ?? errors.tax_id}
                        >
                            <TaxIdInputs
                                labelInput={{ name: 'tax_id_label' }}
                                idInput={{
                                    id: 'customer-tax-id',
                                    name: 'tax_id',
                                }}
                            />
                        </FormField>
                        <FormField
                            id="customer-status"
                            label="Status"
                            error={errors.status}
                        >
                            <NativeSelect
                                id="customer-status"
                                name="status"
                                defaultValue="active"
                            >
                                {options.statuses.map((status) => (
                                    <option
                                        key={status.value}
                                        value={status.value}
                                    >
                                        {status.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        </FormField>
                    </div>

                    <div className="grid gap-5 empty:hidden sm:grid-cols-2">
                        <CustomFieldsFormFields
                            target="customer"
                            errors={errors}
                        />
                    </div>

                    <FormField
                        id="customer-notes"
                        label="Notes"
                        hint="Anything your team should know about this customer."
                        error={errors.notes}
                    >
                        <Textarea id="customer-notes" name="notes" rows={3} />
                    </FormField>

                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onDone}>
                            Cancel
                        </Button>
                        <Button
                            disabled={processing}
                            data-test="save-customer-button"
                        >
                            {processing && <Spinner />}
                            Add customer
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}
