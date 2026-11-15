import { HUE_PRESETS, hueStyle } from '@/lib/colors';
import { cn } from '@/lib/utils';

/**
 * The hues a picker offers: the presets, then no hue (neutral grey).
 */
export const HUE_CHOICES: (number | null)[] = [...HUE_PRESETS, null];

/**
 * A short name for a hue, for screen readers.
 */
export function hueLabel(hue: number | null): string {
    return hue === null ? 'Grey' : `Hue ${hue}°`;
}

/**
 * Colour swatches for a record's hue. Undefined means none picked yet: no
 * field is sent, so a new record gets a hue made from its name. With a name,
 * the choice is sent as a hidden form field ("" for grey).
 */
export default function HuePicker({
    value,
    onChange,
    name,
    className,
}: {
    value: number | null | undefined;
    onChange: (hue: number | null) => void;
    name?: string;
    className?: string;
}) {
    return (
        <div className={cn('flex flex-wrap gap-1.5', className)}>
            {name && value !== undefined && (
                <input type="hidden" name={name} value={value ?? ''} />
            )}
            {HUE_CHOICES.map((hue) => (
                <button
                    key={hue ?? 'grey'}
                    type="button"
                    aria-label={hueLabel(hue)}
                    aria-pressed={value === hue}
                    onClick={() => onChange(hue)}
                    className={cn(
                        'size-6 rounded-md hue-dot ring-offset-2 ring-offset-background',
                        value === hue && 'ring-2 ring-ring',
                    )}
                    style={hueStyle(hue)}
                />
            ))}
        </div>
    );
}
