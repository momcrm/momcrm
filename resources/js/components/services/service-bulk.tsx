import { router } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    CalendarClock,
    Power,
    Receipt,
    RefreshCw,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { billingFrequencyLabel } from '@/components/rates/billing-period';
import { BarButton, BarMenu } from '@/components/tickets/ticket-bulk';
import type { TicketSelection } from '@/components/tickets/ticket-bulk';
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { formatCents } from '@/lib/money';
import {
    change as bulkChange,
    update as bulkUpdate,
} from '@/routes/services/bulk';
import type { Rate } from '@/types';

/**
 * The dark bar that floats at the foot of the services list once services
 * are picked: move them to another rate, change how often they are billed,
 * turn auto-renew or the services on or off, or archive them.
 */
export function ServiceBulkBar({
    selection,
    rates,
    billingPeriods,
    showsArchived,
}: {
    selection: TicketSelection;
    rates: Rate[];
    billingPeriods: number[];
    showsArchived: boolean;
}) {
    const [isSaving, setIsSaving] = useState(false);
    const count = selection.ids.length;

    useEffect(() => {
        if (count === 0) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (
                event.key === 'Escape' &&
                !document.querySelector('[role="menu"]')
            ) {
                selection.clear();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [count, selection]);

    if (count === 0) {
        return null;
    }

    const visitOptions = {
        only: ['services'],
        preserveScroll: true,
        preserveState: true,
        onStart: () => setIsSaving(true),
        onFinish: () => setIsSaving(false),
        onSuccess: () => selection.clear(),
        onError: (errors: Record<string, string>) => {
            const message = Object.values(errors)[0];

            if (message) {
                toast.error(message);
            }
        },
    };

    const update = (fields: Record<string, string | number | boolean | null>) =>
        router.patch(
            bulkUpdate.url(),
            { service_ids: selection.ids, ...fields },
            visitOptions,
        );
    const change = (action: string) =>
        router.post(
            bulkChange.url(),
            { service_ids: selection.ids, action },
            visitOptions,
        );

    return (
        <div
            role="toolbar"
            aria-label="Bulk actions"
            className="fixed bottom-6 left-1/2 z-40 flex -translate-x-1/2 animate-in items-center gap-1 rounded-[10px] bg-foreground py-1.5 pr-1.5 pl-3.5 text-[12.5px] text-background shadow-pop duration-300 fade-in slide-in-from-bottom-2"
        >
            <span className="font-semibold whitespace-nowrap tabular-nums">
                {count} selected
            </span>
            <span className="mx-1 h-[18px] w-px bg-background/20" />

            {showsArchived ? (
                <BarButton
                    disabled={isSaving}
                    onClick={() => change('restore')}
                >
                    <ArchiveRestore className="size-3.5" />
                    Restore
                </BarButton>
            ) : (
                <>
                    <BarMenu
                        icon={<Receipt className="size-3.5" />}
                        label="Rate"
                        disabled={isSaving}
                    >
                        {rates
                            .filter((rate) => !rate.is_archived)
                            .map((rate) => (
                                <DropdownMenuItem
                                    key={rate.id}
                                    onSelect={() =>
                                        update({ rate_id: rate.id })
                                    }
                                >
                                    <span className="flex-1 truncate">
                                        {rate.name}
                                    </span>
                                    <span className="text-xs text-muted-foreground tabular-nums">
                                        {formatCents(rate.price_in_cents)}
                                    </span>
                                </DropdownMenuItem>
                            ))}
                    </BarMenu>

                    <BarMenu
                        icon={<CalendarClock className="size-3.5" />}
                        label="Billed"
                        disabled={isSaving}
                    >
                        <DropdownMenuItem
                            onSelect={() =>
                                update({ billing_period_in_months: null })
                            }
                        >
                            As the rate
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        {billingPeriods.map((months) => (
                            <DropdownMenuItem
                                key={months}
                                onSelect={() =>
                                    update({ billing_period_in_months: months })
                                }
                            >
                                {billingFrequencyLabel(months)}
                            </DropdownMenuItem>
                        ))}
                    </BarMenu>

                    <BarMenu
                        icon={<RefreshCw className="size-3.5" />}
                        label="Auto-renew"
                        disabled={isSaving}
                    >
                        <DropdownMenuItem
                            onSelect={() => update({ auto_renew: true })}
                        >
                            Renew by itself
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() => update({ auto_renew: false })}
                        >
                            Stop renewing
                        </DropdownMenuItem>
                    </BarMenu>

                    <BarMenu
                        icon={<Power className="size-3.5" />}
                        label="Status"
                        disabled={isSaving}
                    >
                        <DropdownMenuItem onSelect={() => change('activate')}>
                            Turn on
                        </DropdownMenuItem>
                        <DropdownMenuItem onSelect={() => change('deactivate')}>
                            Turn off
                        </DropdownMenuItem>
                    </BarMenu>

                    <BarButton
                        disabled={isSaving}
                        onClick={() => change('archive')}
                    >
                        <Archive className="size-3.5" />
                        Archive
                    </BarButton>
                </>
            )}

            <button
                type="button"
                aria-label="Clear selection"
                onClick={selection.clear}
                className="ml-0.5 flex size-[26px] items-center justify-center rounded-md opacity-85 transition hover:bg-background/15 hover:opacity-100"
            >
                <X className="size-3.5" />
            </button>
        </div>
    );
}
