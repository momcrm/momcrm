import { Form, Link } from '@inertiajs/react';
import CustomFieldsFormFields from '@/components/custom-fields/custom-fields-form';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { Contact, CustomerOption } from '@/types';

type Action = { action: string; method: 'post' | 'put' };

export default function ContactForm({
    action,
    contact,
    customers,
    customerId,
    submitLabel,
    cancelHref,
}: {
    action: Action;
    contact?: Contact;
    customers: CustomerOption[];
    customerId?: string | null;
    submitLabel: string;
    cancelHref: string;
}) {
    return (
        <Form {...action} className="max-w-2xl space-y-6">
            {({ processing, errors }) => (
                <>
                    <FormField
                        id="customer_id"
                        label="Customer"
                        error={errors.customer_id}
                    >
                        <NativeSelect
                            id="customer_id"
                            name="customer_id"
                            defaultValue={
                                contact?.customer_id ?? customerId ?? ''
                            }
                            required
                        >
                            <option value="" disabled>
                                Choose a customer
                            </option>
                            {customers.map((customer) => (
                                <option key={customer.id} value={customer.id}>
                                    {customer.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </FormField>

                    <div className="grid gap-6 sm:grid-cols-2">
                        <FormField
                            id="first_name"
                            label="First name"
                            error={errors.first_name}
                        >
                            <Input
                                id="first_name"
                                name="first_name"
                                defaultValue={contact?.first_name}
                                required
                                autoFocus
                            />
                        </FormField>
                        <FormField
                            id="last_name"
                            label="Last name"
                            error={errors.last_name}
                        >
                            <Input
                                id="last_name"
                                name="last_name"
                                defaultValue={contact?.last_name ?? ''}
                            />
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
                                defaultValue={contact?.email ?? ''}
                            />
                        </FormField>
                        <FormField
                            id="position"
                            label="Position"
                            error={errors.position}
                        >
                            <Input
                                id="position"
                                name="position"
                                defaultValue={contact?.position ?? ''}
                            />
                        </FormField>
                        <FormField
                            id="work_phone"
                            label="Work phone"
                            error={errors.work_phone}
                        >
                            <Input
                                id="work_phone"
                                name="work_phone"
                                type="tel"
                                defaultValue={contact?.work_phone ?? ''}
                            />
                        </FormField>
                        <FormField
                            id="mobile_phone"
                            label="Mobile"
                            error={errors.mobile_phone}
                        >
                            <Input
                                id="mobile_phone"
                                name="mobile_phone"
                                type="tel"
                                defaultValue={contact?.mobile_phone ?? ''}
                            />
                        </FormField>
                    </div>

                    <div className="grid gap-6 empty:hidden sm:grid-cols-2">
                        <CustomFieldsFormFields
                            target="contact"
                            values={contact?.custom_fields}
                            errors={errors}
                        />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="is_primary"
                            name="is_primary"
                            value="1"
                            defaultChecked={contact?.is_primary}
                        />
                        <Label htmlFor="is_primary">
                            Primary contact for this customer
                        </Label>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            disabled={processing}
                            data-test="save-contact-button"
                        >
                            {processing && <Spinner />}
                            {submitLabel}
                        </Button>
                        <Button variant="ghost" asChild>
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
