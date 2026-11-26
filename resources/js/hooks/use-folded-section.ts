import { useSyncExternalStore } from 'react';

const STORAGE_KEY = 'sidebar-folded';
const NONE: readonly string[] = [];

const listeners = new Set<() => void>();
let folded: readonly string[] | null = null;

const read = (): readonly string[] => {
    try {
        const stored: unknown = JSON.parse(
            localStorage.getItem(STORAGE_KEY) ?? '[]',
        );

        return Array.isArray(stored)
            ? stored.filter((key): key is string => typeof key === 'string')
            : NONE;
    } catch {
        return NONE;
    }
};

const snapshot = (): readonly string[] => (folded ??= read());

/**
 * Whether the person has folded one of the sidebar's sections away, and a
 * way to fold or unfold it. The choice is kept in this browser.
 */
export function useFoldedSection(key: string): [boolean, () => void] {
    const all = useSyncExternalStore(
        (callback) => {
            listeners.add(callback);

            return () => listeners.delete(callback);
        },
        snapshot,
        () => NONE,
    );

    const toggle = () => {
        const current = snapshot();

        folded = current.includes(key)
            ? current.filter((other) => other !== key)
            : [...current, key];

        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(folded));
        } catch {
            // Private mode or blocked storage: the choice lasts for this visit.
        }

        listeners.forEach((listener) => listener());
    };

    return [all.includes(key), toggle];
}
