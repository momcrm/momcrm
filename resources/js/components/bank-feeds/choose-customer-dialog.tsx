import { router, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { formatCents } from '@/lib/money';
import { cn } from '@/lib/utils';
import { customer as assignCustomer } from '@/routes/bank-feeds';
import type { BankFeedCustomer, BankTransaction } from '@/types';

/**
 * Text to remember a deposit by: its description without the numbers and
 * dates that change each time, or the bank's "Direct Credit" lead-in.
 */
function ruleText(description: string): string {
    return description
        .replace(/^\s*direct\s+credit\b/i, ' ')
        .replace(/[\d/.:#-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, 100);
}

/**
 * Picks the customer a deposit is from, among those billed in its account's
 * currency. The customers are loaded when it opens. Owners and admins can
 * have later deposits like it matched to the customer by a rule.
 */
export default function ChooseCustomerDialog({
    line,
    onOpenChange,
    onChosen,
}: {
    line: BankTransaction | null;
    onOpenChange: (open: boolean) => void;
    /** Called with the line once its customer is saved. */
    onChosen?: (line: BankTransaction, customer: BankFeedCustomer) => void;
}) {
    const { customers, canManageBankFeeds } = usePage<{
        customers?: BankFeedCustomer[];
    }>().props;
    const [isRemembering, setIsRemembering] = useState(false);
    const [remember, setRemember] = useState('');
    const [search, setSearch] = useState('');
    const [picked, setPicked] = useState<string | null>(null);
    const [error, setError] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        if (line === null) {
            return;
        }

        setSearch('');
        setPicked(line.customer_id);
        setError({});
        setIsRemembering(false);
        setRemember(ruleText(line.description));

        if (customers === undefined) {
            router.reload({ only: ['customers'] });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [line?.id]);

    const matching = useMemo(() => {
        const term = search.trim().toLowerCase();

        return (customers ?? []).filter(
            (customer) =>
                customer.currency === line?.currency &&
                (term === '' || customer.name.toLowerCase().includes(term)),
        );
    }, [customers, search, line?.currency]);

    const save = () => {
        if (!line || !picked) {
            return;
        }

        router.patch(
            assignCustomer.url(line.id),
            {
                customer_id: picked,
                remember: isRemembering ? remember : null,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: setError,
                onSuccess: () => {
                    const chosen = customers?.find(
                        (option) => option.id === picked,
                    );

                    onOpenChange(false);

                    if (chosen) {
                        onChosen?.(line, chosen);
                    }
                },
            },
        );
    };

    return (
        <Dialog open={line !== null} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogTitle>Who paid this?</DialogTitle>
                <DialogDescription>
                    {line
                        ? `${formatCents(line.amount_in_cents, line.currency ?? undefined)} · ${line.description}`
                        : ''}
                </DialogDescription>
                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        aria-label="Search customers"
                        placeholder="Search customers"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        className="pl-8"
                        autoFocus
                    />
                </div>
                {customers === undefined ? (
                    <div className="space-y-2">
                        <Skeleton className="h-8 w-full" />
                        <Skeleton className="h-8 w-full" />
                        <Skeleton className="h-8 w-full" />
                    </div>
                ) : matching.length === 0 ? (
                    <p className="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground">
                        No customers billed in {line?.currency} match.
                    </p>
                ) : (
                    <ul
                        role="listbox"
                        aria-label="Customers"
                        className="max-h-72 divide-y overflow-y-auto rounded-lg border"
                    >
                        {matching.map((customer) => (
                            <li
                                key={customer.id}
                                role="option"
                                aria-selected={picked === customer.id}
                            >
                                <button
                                    type="button"
                                    onClick={() => setPicked(customer.id)}
                                    className={cn(
                                        'w-full px-3 py-2 text-left text-sm hover:bg-muted/60',
                                        picked === customer.id &&
                                            'bg-primary/10 font-medium text-primary',
                                    )}
                                >
                                    {customer.name}
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
                <InputError message={error.customer_id} />
                {canManageBankFeeds && (
                    <div className="space-y-2">
                        <Label className="flex items-center gap-2 font-normal">
                            <Checkbox
                                checked={isRemembering}
                                onCheckedChange={(isOn) =>
                                    setIsRemembering(isOn === true)
                                }
                            />
                            Remember: deposits containing this text are from
                            them
                        </Label>
                        {isRemembering && (
                            <Input
                                aria-label="Text to remember"
                                value={remember}
                                onChange={(event) =>
                                    setRemember(event.target.value)
                                }
                            />
                        )}
                        <InputError message={error.remember} />
                    </div>
                )}
                <DialogFooter>
                    <Button disabled={!picked || processing} onClick={save}>
                        {processing && <Spinner />}
                        Choose customer
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
