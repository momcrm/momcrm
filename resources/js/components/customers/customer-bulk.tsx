import { router } from '@inertiajs/react';
import { CircleDot, Receipt, Shapes, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { useCustomerTypes } from '@/components/customers/customer-meta';
import { BarMenu } from '@/components/tickets/ticket-bulk';
import type { TicketSelection } from '@/components/tickets/ticket-bulk';
import { ChoiceTag } from '@/components/tickets/ticket-meta';
import {
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { update } from '@/routes/customers/bulk';
import type { Option } from '@/types';

/**
 * The dark bar that floats at the foot of the customer list once customers
 * are picked: change their type, billing type or status.
 */
export function CustomerBulkBar({
    selection,
    billingTypes,
    statuses,
}: {
    selection: TicketSelection;
    billingTypes: Option[];
    statuses: Option[];
}) {
    const types = useCustomerTypes();
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

    const change = (fields: Record<string, string | null>) =>
        router.patch(
            update.url(),
            { customer_ids: selection.ids, ...fields },
            {
                only: ['customers'],
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
            },
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

            <BarMenu
                icon={<Shapes className="size-3.5" />}
                label="Type"
                disabled={isSaving}
            >
                {types.map((type) => (
                    <DropdownMenuItem
                        key={type.id}
                        onSelect={() => change({ type_id: type.id })}
                    >
                        <ChoiceTag choice={type} />
                    </DropdownMenuItem>
                ))}
                {types.length > 0 && <DropdownMenuSeparator />}
                <DropdownMenuItem onSelect={() => change({ type_id: null })}>
                    No type
                </DropdownMenuItem>
            </BarMenu>

            <BarMenu
                icon={<Receipt className="size-3.5" />}
                label="Billing"
                disabled={isSaving}
            >
                {billingTypes.map((billingType) => (
                    <DropdownMenuItem
                        key={billingType.value}
                        onSelect={() =>
                            change({ billing_type: billingType.value })
                        }
                    >
                        {billingType.label}
                    </DropdownMenuItem>
                ))}
            </BarMenu>

            <BarMenu
                icon={<CircleDot className="size-3.5" />}
                label="Status"
                disabled={isSaving}
            >
                {statuses.map((status) => (
                    <DropdownMenuItem
                        key={status.value}
                        onSelect={() => change({ status: status.value })}
                    >
                        {status.label}
                    </DropdownMenuItem>
                ))}
            </BarMenu>

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
