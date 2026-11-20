import { Form, Head } from '@inertiajs/react';
import UpdateBillingDetailsController from '@/actions/App/Team/Controllers/UpdateBillingDetailsController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { TeamBilling } from '@/types';

/** What tax IDs are called in the countries the app bills in. */
const taxIdLabels = [
    'ABN',
    'GST No.',
    'NZBN',
    'VAT',
    'UEN',
    'TIN',
    'BN',
    'BRN',
    'EIN',
];

export default function Billing({
    teamName,
    billing,
}: {
    teamName: string;
    billing: TeamBilling;
}) {
    return (
        <>
            <Head title="Billing details" />

            <h1 className="sr-only">Billing details</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Billing details"
                    description="Who your invoices are from. These show at the top of every invoice and its PDF."
                />

                <Form
                    {...UpdateBillingDetailsController.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="billing_name">
                                    Business name
                                </Label>
                                <Input
                                    id="billing_name"
                                    name="billing_name"
                                    defaultValue={billing.billing_name ?? ''}
                                    placeholder={teamName}
                                />
                                <p className="text-sm text-muted-foreground">
                                    Your registered or trading name. Leave it
                                    blank to use your team name, {teamName}.
                                </p>
                                <InputError message={errors.billing_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tax_id">Tax ID</Label>
                                <div className="flex gap-2">
                                    <Input
                                        aria-label="What your tax ID is called"
                                        name="tax_id_label"
                                        list="tax-id-labels"
                                        defaultValue={
                                            billing.tax_id_label ?? ''
                                        }
                                        placeholder="ABN"
                                        className="w-28 shrink-0"
                                    />
                                    <datalist id="tax-id-labels">
                                        {taxIdLabels.map((label) => (
                                            <option key={label} value={label} />
                                        ))}
                                    </datalist>
                                    <Input
                                        id="tax_id"
                                        name="tax_id"
                                        defaultValue={billing.tax_id ?? ''}
                                        placeholder="12 345 678 901"
                                    />
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    Name it the way your country does, e.g. ABN,
                                    VAT or GST No. With a tax ID, invoices that
                                    charge tax are headed “Tax invoice” (“VAT
                                    invoice” for VAT).
                                </p>
                                <InputError
                                    message={
                                        errors.tax_id_label ?? errors.tax_id
                                    }
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="billing_email">Email</Label>
                                    <Input
                                        id="billing_email"
                                        name="billing_email"
                                        type="email"
                                        defaultValue={
                                            billing.billing_email ?? ''
                                        }
                                        placeholder="accounts@example.com"
                                    />
                                    <InputError
                                        message={errors.billing_email}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="billing_phone">Phone</Label>
                                    <Input
                                        id="billing_phone"
                                        name="billing_phone"
                                        type="tel"
                                        defaultValue={
                                            billing.billing_phone ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.billing_phone}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="billing_address">Address</Label>
                                <Textarea
                                    id="billing_address"
                                    name="billing_address"
                                    rows={3}
                                    defaultValue={billing.billing_address ?? ''}
                                />
                                <InputError message={errors.billing_address} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="payment_details">
                                    How to pay
                                </Label>
                                <Textarea
                                    id="payment_details"
                                    name="payment_details"
                                    rows={4}
                                    defaultValue={billing.payment_details ?? ''}
                                    placeholder={
                                        'Bank: …\nAccount name: …\nAccount number: …'
                                    }
                                />
                                <p className="text-sm text-muted-foreground">
                                    Bank details or a payment link. Shown on
                                    invoices that are still to be paid.
                                </p>
                                <InputError message={errors.payment_details} />
                            </div>

                            <Button disabled={processing}>Save</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
