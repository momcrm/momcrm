import { useSyncExternalStore } from 'react';

export const accents = [
    'indigo',
    'blue',
    'teal',
    'violet',
    'rose',
    'green',
] as const;
export type Accent = (typeof accents)[number];
export type Density = 'comfortable' | 'compact';

type Preferences = { accent: Accent; density: Density };

const listeners = new Set<() => void>();
let current: Preferences = { accent: 'indigo', density: 'comfortable' };

const read = (): Preferences => {
    try {
        const accent = localStorage.getItem('accent') as Accent | null;
        const density = localStorage.getItem('density') as Density | null;

        return {
            accent: accent && accents.includes(accent) ? accent : 'indigo',
            density: density === 'compact' ? 'compact' : 'comfortable',
        };
    } catch {
        return { accent: 'indigo', density: 'comfortable' };
    }
};

const apply = ({ accent, density }: Preferences): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;

    if (accent === 'indigo') {
        delete root.dataset.accent;
    } else {
        root.dataset.accent = accent;
    }

    if (density === 'compact') {
        root.dataset.density = 'compact';
    } else {
        delete root.dataset.density;
    }
};

/**
 * Applies the saved accent colour and density before the app renders.
 */
export function initializePreferences(): void {
    if (typeof window === 'undefined') {
        return;
    }

    current = read();
    apply(current);
}

/**
 * The person's accent colour and density, kept in this browser.
 */
export function usePreferences() {
    const preferences = useSyncExternalStore(
        (callback) => {
            listeners.add(callback);

            return () => listeners.delete(callback);
        },
        () => current,
        () => current,
    );

    const update = (changes: Partial<Preferences>) => {
        current = { ...current, ...changes };

        try {
            localStorage.setItem('accent', current.accent);
            localStorage.setItem('density', current.density);
        } catch {
            // Private mode or blocked storage: the choice lasts for this visit.
        }

        apply(current);
        listeners.forEach((listener) => listener());
    };

    return { ...preferences, update };
}
