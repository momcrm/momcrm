import { usePage } from '@inertiajs/react';
import { liveOrInUse } from '@/components/tickets/ticket-meta';
import { hueStyle } from '@/lib/colors';
import { cn } from '@/lib/utils';
import type { Choice, LabelTag } from '@/types';

/**
 * The team's live labels in order, shared by tickets, projects and assets,
 * plus any archived ones among the given labels (such as the record's own).
 */
export function useLabels(
    ...inUseIds: (string | null | undefined)[]
): Choice[] {
    return liveOrInUse(usePage().props.choices?.label ?? [], inUseIds);
}

/**
 * A record's labels as small chips, each with its colour.
 */
export function LabelChips({
    labels,
    className,
}: {
    labels: LabelTag[];
    className?: string;
}) {
    if (labels.length === 0) {
        return null;
    }

    return (
        <span className={cn('flex flex-wrap gap-1', className)}>
            {labels.map((label) => (
                <span
                    key={label.id}
                    className="inline-flex items-center gap-1 rounded-[5px] border bg-card px-1.5 py-px text-[11px] font-medium text-muted-foreground"
                >
                    <span
                        aria-hidden
                        className="size-1.5 rounded-full hue-dot"
                        style={hueStyle(label.hue)}
                    />
                    {label.name}
                </span>
            ))}
        </span>
    );
}

/**
 * Label toggles for a form: sends `label_ids[]`, plus `labels_sent` so
 * turning every label off still clears them. Shows nothing when the team
 * has no labels yet.
 */
export function LabelField({
    selected,
    onChange,
    error,
}: {
    selected: string[];
    onChange: (ids: string[]) => void;
    error?: string;
}) {
    const labels = useLabels(...selected);

    if (labels.length === 0) {
        return null;
    }

    return (
        <div className="grid gap-2">
            <span className="text-sm font-medium">Labels</span>
            <div className="flex flex-wrap gap-1.5">
                {labels.map((label) => {
                    const isOn = selected.includes(label.id);

                    return (
                        <button
                            key={label.id}
                            type="button"
                            aria-pressed={isOn}
                            onClick={() =>
                                onChange(
                                    isOn
                                        ? selected.filter(
                                              (id) => id !== label.id,
                                          )
                                        : [...selected, label.id],
                                )
                            }
                            className={cn(
                                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs',
                                isOn
                                    ? 'border-primary/30 bg-primary/8 text-foreground'
                                    : 'text-muted-foreground',
                            )}
                        >
                            <span
                                aria-hidden
                                className="size-1.5 rounded-full hue-dot"
                                style={hueStyle(label.hue)}
                            />
                            {label.name}
                        </button>
                    );
                })}
            </div>
            <input type="hidden" name="labels_sent" value="1" />
            {selected.map((id) => (
                <input key={id} type="hidden" name="label_ids[]" value={id} />
            ))}
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}
