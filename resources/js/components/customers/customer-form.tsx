import { Form, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useCustomerTypes } from '@/components/customers/customer-meta';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Customer, Option } from '@/types';

type Action = { action: string; method: 'post' | 'put' };

export default function CustomerForm({
    action,
    customer,
    statuses,
    billingTypes,
    submitLabel,
    cancelHref,
    children,
}: {
    action: Action;
    customer?: Customer;
    statuses: Option[];
    billingTypes: Option[];
    submitLabel: string;
    cancelHref?: string;
    /** Extra fields, given the form's errors, shown above the buttons. */
    children?: (errors: Record<string, string>) => ReactNode;
}) {
    const types = useCustomerTypes(customer?.type_id);

    return (
        <Form {...action} className="max-w-2xl space-y-6">
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-6 sm:grid-cols-[1fr_8rem]">
                        <FormField id="name" label="Name" error={errors.name}>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={customer?.name}
                                required
                                autoFocus
                            />
                        </FormField>
                        <FormField
                            id="prefix"
                            label="Prefix"
                            error={errors.prefix}
                        >
                            <Input
                                id="prefix"
                                name="prefix"
                                maxLength={8}
                                placeholder="ACME"
                                className="font-mono uppercase"
                                defaultValue={customer?.prefix ?? ''}
                            />
                        </FormField>
                    </div>

                    <div className="grid gap-6 sm:grid-cols-2">
                        <FormField
                            id="type_id"
                            label="Type"
                            error={errors.type_id}
                        >
                            <NativeSelect
                                id="type_id"
                                name="type_id"
                                defaultValue={customer?.type_id ?? ''}
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
                            id="billing_type"
                            label="Billing"
                            error={errors.billing_type}
                        >
                            <NativeSelect
                                id="billing_type"
                                name="billing_type"
                                defaultValue={
                                    customer?.billing_type ?? 'manual'
                                }
                            >
                                {billingTypes.map((billingType) => (
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
                            id="email"
                            label="Email"
                            error={errors.email}
                        >
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                defaultValue={customer?.email ?? ''}
                            />
                        </FormField>
                        <FormField
                            id="phone"
                            label="Phone"
                            error={errors.phone}
                        >
                            <Input
                                id="phone"
                                name="phone"
                                type="tel"
                                defaultValue={customer?.phone ?? ''}
                            />
                        </FormField>
                        <FormField
                            id="website"
                            label="Website"
                            error={errors.website}
                        >
                            <Input
                                id="website"
                                name="website"
                                type="url"
                                placeholder="https://"
                                defaultValue={customer?.website ?? ''}
                            />
                        </FormField>
                        <FormField id="abn" label="ABN" error={errors.abn}>
                            <Input
                                id="abn"
                                name="abn"
                                defaultValue={customer?.abn ?? ''}
                            />
                        </FormField>
                        <FormField
                            id="status"
                            label="Status"
                            error={errors.status}
                        >
                            <NativeSelect
                                id="status"
                                name="status"
                                defaultValue={customer?.status ?? 'active'}
                            >
                                {statuses.map((status) => (
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

                    <FormField
                        id="notes"
                        label="Notes"
                        hint="Anything your team should know about this customer."
                        error={errors.notes}
                    >
                        <Textarea
                            id="notes"
                            name="notes"
                            rows={4}
                            defaultValue={customer?.notes ?? ''}
                        />
                    </FormField>

                    {children?.(errors)}

                    <div className="flex items-center gap-2">
                        <Button
                            disabled={processing}
                            data-test="save-customer-button"
                        >
                            {processing && <Spinner />}
                            {submitLabel}
                        </Button>
                        {cancelHref && (
                            <Button variant="ghost" asChild>
                                <Link href={cancelHref}>Cancel</Link>
                            </Button>
                        )}
                    </div>
                </>
            )}
        </Form>
    );
}
