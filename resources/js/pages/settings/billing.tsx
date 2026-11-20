import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import UpdateBillingDetailsController from '@/actions/App/Team/Controllers/UpdateBillingDetailsController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TaxIdInputs from '@/components/tax-id-inputs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { TeamBilling } from '@/types';

/**
 * What invoice numbers start with, previewed as the next invoice would
 * show it.
 */
function InvoicePrefixField({
    defaultValue,
    error,
}: {
    defaultValue: string;
    error?: string;
}) {
    const [prefix, setPrefix] = useState(defaultValue);

    return (
        <div className="grid gap-2">
            <Label htmlFor="invoice_prefix">Invoice number prefix</Label>
            <Input
                id="invoice_prefix"
                name="invoice_prefix"
                value={prefix}
                onChange={(event) => setPrefix(event.target.value)}
                maxLength={12}
                className="w-40"
            />
            <p className="text-sm text-muted-foreground">
                Invoices are numbered like{' '}
                <span className="font-mono text-foreground">{prefix}0042</span>.
                Leave it blank for plain numbers. Invoices already drafted keep
                the prefix they were made with.
            </p>
            <InputError message={error} />
        </div>
    );
}

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
                                <TaxIdInputs
                                    labelInput={{
                                        name: 'tax_id_label',
                                        defaultValue:
                                            billing.tax_id_label ?? '',
                                    }}
                                    idInput={{
                                        id: 'tax_id',
                                        name: 'tax_id',
                                        defaultValue: billing.tax_id ?? '',
                                    }}
                                />
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

                            <div className="grid gap-2">
                                <Label htmlFor="bpay_biller_code">
                                    BPAY biller code
                                </Label>
                                <Input
                                    id="bpay_biller_code"
                                    name="bpay_biller_code"
                                    inputMode="numeric"
                                    defaultValue={
                                        billing.bpay_biller_code ?? ''
                                    }
                                    className="max-w-40 font-mono"
                                />
                                <p className="text-sm text-muted-foreground">
                                    From your bank, if customers can pay you by
                                    BPAY. Invoices then show it with each
                                    customer’s reference number, and BPAY
                                    payments match to them on import.
                                </p>
                                <InputError message={errors.bpay_biller_code} />
                            </div>

                            <InvoicePrefixField
                                defaultValue={billing.invoice_prefix}
                                error={errors.invoice_prefix}
                            />

                            <Button disabled={processing}>Save</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
