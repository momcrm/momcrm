import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * A large, tappable answer for an onboarding question.
 */
export default function OptionCard({
    label,
    description,
    selected,
    badge,
    onSelect,
}: {
    label: string;
    description?: string;
    selected: boolean;
    badge?: string;
    onSelect: () => void;
}) {
    return (
        <button
            type="button"
            aria-pressed={selected}
            onClick={onSelect}
            className={cn(
                'flex w-full items-start justify-between gap-4 rounded-lg border p-4 text-left transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                selected && 'border-primary bg-accent',
            )}
        >
            <span className="flex flex-col gap-1">
                <span className="flex items-center gap-2 font-medium">
                    {label}
                    {badge && (
                        <span className="rounded-full bg-primary px-2 py-0.5 text-xs text-primary-foreground">
                            {badge}
                        </span>
                    )}
                </span>
                {description && (
                    <span className="text-sm text-muted-foreground">
                        {description}
                    </span>
                )}
            </span>
            {selected && <Check className="size-5 shrink-0 text-primary" />}
        </button>
    );
}
