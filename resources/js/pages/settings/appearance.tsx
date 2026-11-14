import { Head } from '@inertiajs/react';
import { Check, Monitor, Moon, Sun } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import Heading from '@/components/heading';
import { useAppearance } from '@/hooks/use-appearance';
import type { Appearance } from '@/hooks/use-appearance';
import { accents, usePreferences } from '@/hooks/use-preferences';
import type { Accent, Density } from '@/hooks/use-preferences';
import { cn } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';

const themes: {
    value: Appearance;
    label: string;
    icon: LucideIcon;
    preview: string;
}[] = [
    { value: 'light', label: 'Light', icon: Sun, preview: 'bg-[#fbfaf8]' },
    { value: 'dark', label: 'Dark', icon: Moon, preview: 'bg-[#1a1a19]' },
    {
        value: 'system',
        label: 'System',
        icon: Monitor,
        preview: 'bg-gradient-to-r from-[#fbfaf8] from-50% to-[#1a1a19] to-50%',
    },
];

const accentSwatches: Record<Accent, string> = {
    indigo: 'bg-[#4b5bd6]',
    blue: 'bg-[#2f6fd1]',
    teal: 'bg-[#1e8580]',
    violet: 'bg-[#7652c2]',
    rose: 'bg-[#bc4270]',
    green: 'bg-[#25754b]',
};

const densities: { value: Density; label: string; description: string }[] = [
    {
        value: 'comfortable',
        label: 'Comfortable',
        description: 'More room around everything.',
    },
    {
        value: 'compact',
        label: 'Compact',
        description: 'Fit more on the screen.',
    },
];

/**
 * How MomCRM looks in this browser: theme, accent colour and density.
 */
export default function AppearanceSettings() {
    const { appearance, updateAppearance } = useAppearance();
    const { accent, density, update } = usePreferences();

    return (
        <>
            <Head title="Appearance settings" />

            <h1 className="sr-only">Appearance settings</h1>

            <div className="space-y-8">
                <Heading
                    variant="small"
                    title="Appearance"
                    description="How MomCRM looks on this device."
                />

                <Section title="Theme">
                    <div className="grid grid-cols-3 gap-3">
                        {themes.map((theme) => (
                            <button
                                key={theme.value}
                                type="button"
                                aria-pressed={appearance === theme.value}
                                onClick={() => updateAppearance(theme.value)}
                                className={cn(
                                    'overflow-hidden rounded-lg border bg-card text-left shadow-xs transition hover:border-border-strong',
                                    appearance === theme.value &&
                                        'border-primary ring-2 ring-ring',
                                )}
                            >
                                <span
                                    className={cn(
                                        'block h-16 border-b',
                                        theme.preview,
                                    )}
                                />
                                <span className="flex items-center gap-1.5 px-3 py-2 text-sm">
                                    <theme.icon className="size-3.5" />
                                    {theme.label}
                                </span>
                            </button>
                        ))}
                    </div>
                </Section>

                <Section title="Accent colour">
                    <div className="flex flex-wrap gap-2">
                        {accents.map((value) => (
                            <button
                                key={value}
                                type="button"
                                aria-label={value}
                                aria-pressed={accent === value}
                                onClick={() => update({ accent: value })}
                                className={cn(
                                    'flex size-8 items-center justify-center rounded-full text-white ring-offset-2 ring-offset-background',
                                    accentSwatches[value],
                                    accent === value &&
                                        'ring-2 ring-foreground/40',
                                )}
                            >
                                {accent === value && (
                                    <Check className="size-4" />
                                )}
                            </button>
                        ))}
                    </div>
                </Section>

                <Section title="Density">
                    <div className="grid gap-3 sm:grid-cols-2">
                        {densities.map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                aria-pressed={density === option.value}
                                onClick={() =>
                                    update({ density: option.value })
                                }
                                className={cn(
                                    'rounded-lg border bg-card px-3 py-2.5 text-left shadow-xs hover:border-border-strong',
                                    density === option.value &&
                                        'border-primary ring-2 ring-ring',
                                )}
                            >
                                <span className="block text-sm font-medium">
                                    {option.label}
                                </span>
                                <span className="block text-xs text-muted-foreground">
                                    {option.description}
                                </span>
                            </button>
                        ))}
                    </div>
                </Section>
            </div>
        </>
    );
}

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="space-y-3">
            <h2 className="text-sm font-medium">{title}</h2>
            {children}
        </section>
    );
}

AppearanceSettings.layout = {
    breadcrumbs: [{ title: 'Appearance settings', href: editAppearance() }],
};
