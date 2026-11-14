import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toUrl } from '@/lib/utils';
import { shortcutDestinations } from '@/lib/workspace-navigation';

const SEQUENCE_TIMEOUT_MS = 1000;

function isTyping(target: EventTarget | null): boolean {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    return (
        target.isContentEditable ||
        ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)
    );
}

/**
 * App-wide keyboard shortcuts: ⌘K / Ctrl+K toggles the command palette, and
 * "G" followed by a letter jumps to a page (G then T opens tickets).
 */
export function useShortcuts({
    onTogglePalette,
}: {
    onTogglePalette: () => void;
}): void {
    useEffect(() => {
        let awaitingDestination = false;
        let sequenceTimeout: ReturnType<typeof setTimeout> | undefined;

        const handleKeyDown = (event: KeyboardEvent) => {
            if (
                event.key.toLowerCase() === 'k' &&
                (event.metaKey || event.ctrlKey)
            ) {
                event.preventDefault();
                onTogglePalette();

                return;
            }

            if (
                event.metaKey ||
                event.ctrlKey ||
                event.altKey ||
                isTyping(event.target)
            ) {
                return;
            }

            const key = event.key.toLowerCase();

            if (awaitingDestination) {
                awaitingDestination = false;
                clearTimeout(sequenceTimeout);

                const destination = shortcutDestinations().find(
                    (item) => item.shortcut === key,
                );

                if (destination) {
                    event.preventDefault();
                    router.visit(toUrl(destination.href));
                }

                return;
            }

            if (key === 'g') {
                awaitingDestination = true;
                sequenceTimeout = setTimeout(() => {
                    awaitingDestination = false;
                }, SEQUENCE_TIMEOUT_MS);
            }
        };

        window.addEventListener('keydown', handleKeyDown);

        return () => {
            window.removeEventListener('keydown', handleKeyDown);
            clearTimeout(sequenceTimeout);
        };
    }, [onTogglePalette]);
}
