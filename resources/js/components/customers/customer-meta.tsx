import { usePage } from '@inertiajs/react';
import SoftBadge from '@/components/soft-badge';
import { liveOrInUse } from '@/components/tickets/ticket-meta';
import type { Choice, CustomerBillingType, Tone } from '@/types';

/**
 * The team's live customer types in order, plus any archived ones among the
 * given types (such as the customer's own).
 */
export function useCustomerTypes(
    ...inUseIds: (string | null | undefined)[]
): Choice[] {
    return liveOrInUse(usePage().props.choices?.customer_type ?? [], inUseIds);
}

export const billingTypeLabels: Record<CustomerBillingType, string> = {
    automatic: 'Automatic',
    manual: 'Manual',
    excluded: 'Excluded',
};

const billingTypeTones: Record<CustomerBillingType, Tone> = {
    automatic: 'success',
    manual: 'warning',
    excluded: 'neutral',
};

/**
 * How a customer is billed, as a tinted badge: automatic green, manual amber,
 * excluded grey.
 */
export function BillingTypeBadge({
    billingType,
}: {
    billingType: CustomerBillingType;
}) {
    return (
        <SoftBadge tone={billingTypeTones[billingType]}>
            {billingTypeLabels[billingType]}
        </SoftBadge>
    );
}

/**
 * A customer's prefix as a small monospaced code, such as ACME.
 */
export function PrefixCode({ prefix }: { prefix: string | null }) {
    if (!prefix) {
        return null;
    }

    return (
        <span className="rounded-[4px] border bg-muted px-1 font-mono text-[11px] leading-4 text-soft-foreground">
            {prefix}
        </span>
    );
}
