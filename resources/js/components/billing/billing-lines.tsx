import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { formatCents } from '@/lib/money';
import type { BillingLine } from '@/types';

/**
 * Lines to bill, each with a tick, as Amphora's Verify step lists them.
 */
export default function BillingLines({
    lines,
    currency,
    ticked,
    onToggle,
}: {
    lines: BillingLine[];
    currency: string;
    ticked: Set<string>;
    onToggle: (key: string, isOn: boolean) => void;
}) {
    return (
        <ul className="divide-y rounded-lg border bg-card">
            {lines.map((line) => (
                <li
                    key={line.key}
                    className="flex items-center gap-3 px-3 py-2 text-sm"
                >
                    <Checkbox
                        id={`line-${line.key}`}
                        checked={ticked.has(line.key)}
                        onCheckedChange={(isOn) =>
                            onToggle(line.key, isOn === true)
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
                                ? 'Waiting line'
                                : 'Service'}
                            {line.quantity > 1 &&
                                ` · ${line.quantity} × ${formatCents(line.unit_price_in_cents, currency)}`}
                        </span>
                    </Label>
                    <span className="tabular-nums">
                        {formatCents(line.total_in_cents, currency)}
                    </span>
                </li>
            ))}
        </ul>
    );
}

/** Today's month as YYYY-MM in the browser's time zone. */
export function thisMonth(): string {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}
