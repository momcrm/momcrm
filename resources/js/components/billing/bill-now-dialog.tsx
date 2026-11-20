import { router, useHttp } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { useInvoiceDialog } from '@/components/invoices/invoice-dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { formatCents } from '@/lib/money';
import { bill, billing } from '@/routes/customers';
import type { CustomerBilling } from '@/types';

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
});

/** Today as YYYY-MM-DD in the browser's time zone. */
function today(): string {
    const now = new Date();
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

/**
 * Bills a customer now, as Amphora's company billing does: shows what they
 * are due by a day (service periods starting by then, and waiting one-off
 * charges), all ticked, and drafts one invoice from the ticked lines, then
 * opens it.
 */
export default function BillNowDialog({ customerId }: { customerId: string }) {
    const [open, setOpen] = useState(false);
    const [day, setDay] = useState(today);
    const [data, setData] = useState<CustomerBilling | null>(null);
    const [ticked, setTicked] = useState<Set<string>>(new Set());
    const [error, setError] = useState<string | undefined>();
    const [processing, setProcessing] = useState(false);
    const http = useHttp<Record<string, never>, CustomerBilling>();
    const { openInvoice } = useInvoiceDialog();

    useEffect(() => {
        if (!open) {
            return;
        }

        setData(null);
        setError(undefined);
        http.cancel();
        void http
            .get(billing.url(customerId, { query: { date: day } }))
            .then((loaded) => {
                setData(loaded);
                setTicked(new Set(loaded.lines.map((line) => line.key)));
            })
            .catch(() => undefined);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, day, customerId]);

    const lines = data?.lines ?? [];
    const money = (cents: number) =>
        formatCents(cents, data?.customer.currency);
    const total = lines
        .filter((line) => ticked.has(line.key))
        .reduce((sum, line) => sum + line.total_in_cents, 0);

    const toggle = (key: string, isOn: boolean) =>
        setTicked((current) => {
            const next = new Set(current);

            if (isOn) {
                next.add(key);
            } else {
                next.delete(key);
            }

            return next;
        });

    const create = () =>
        router.post(
            bill.url(customerId),
            { date: day, line_keys: [...ticked] },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setError(Object.values(errors)[0]),
                onFlash: (flash) => {
                    const invoiceId = flash.invoiceId as string | undefined;

                    if (invoiceId) {
                        setOpen(false);
                        openInvoice(invoiceId);
                    }
                },
            },
        );

    return (
        <>
            <Button variant="outline" size="sm" onClick={() => setOpen(true)}>
                <FileText />
                Bill now
            </Button>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[92vh] overflow-y-auto sm:max-w-2xl">
                    <DialogTitle>Bill now</DialogTitle>
                    <DialogDescription>
                        {data
                            ? `What ${data.customer.name} is due: their service periods starting by the day below, and their waiting one-off charges. The invoice is drafted, due ${dateFormatter.format(new Date(data.due_on))}.`
                            : 'Loading…'}
                    </DialogDescription>

                    <div className="grid max-w-56 gap-2">
                        <Label htmlFor="bill-now-day">
                            Bill periods starting by
                        </Label>
                        <Input
                            id="bill-now-day"
                            type="date"
                            value={day}
                            onChange={(event) =>
                                event.target.value && setDay(event.target.value)
                            }
                        />
                    </div>

                    {data === null ? (
                        <div className="space-y-2">
                            <Skeleton className="h-9 w-full" />
                            <Skeleton className="h-9 w-full" />
                        </div>
                    ) : lines.length === 0 ? (
                        <p className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                            Nothing is due by then. Every service period
                            starting by that day is on an invoice already, and
                            there are no waiting charges.
                        </p>
                    ) : (
                        <ul className="divide-y rounded-lg border">
                            {lines.map((line) => (
                                <li
                                    key={line.key}
                                    className="flex items-center gap-3 px-3 py-2 text-sm"
                                >
                                    <Checkbox
                                        id={`line-${line.key}`}
                                        checked={ticked.has(line.key)}
                                        onCheckedChange={(isOn) =>
                                            toggle(line.key, isOn === true)
                                        }
                                    />
                                    <Label
                                        htmlFor={`line-${line.key}`}
                                        className="min-w-0 flex-1 font-normal"
                                    >
                                        <span className="block truncate">
                                            {line.description}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {line.kind === 'charge'
                                                ? 'One-off charge'
                                                : 'Service'}
                                            {line.quantity > 1 &&
                                                ` · ${line.quantity} × ${money(line.unit_price_in_cents)}`}
                                        </span>
                                    </Label>
                                    <span className="tabular-nums">
                                        {money(line.total_in_cents)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}

                    <InputError message={error} />

                    <DialogFooter className="items-center gap-3">
                        {lines.length > 0 && (
                            <span className="mr-auto text-sm text-muted-foreground">
                                {ticked.size} of {lines.length} lines ·{' '}
                                {money(total)} before tax
                            </span>
                        )}
                        <Button
                            disabled={processing || ticked.size === 0}
                            onClick={create}
                        >
                            {processing && <Spinner />}
                            Draft invoice
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
