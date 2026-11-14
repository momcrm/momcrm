import { router } from '@inertiajs/react';

type QueryValue = string | number | string[] | null | undefined;

/**
 * Reloads the current page with some query parameters changed and the rest
 * kept. Empty values remove their parameter; any change resets the page.
 */
export function visitWithQuery(
    changes: Record<string, QueryValue>,
    options: { only?: string[]; keepPage?: boolean } = {},
): void {
    const params = new URLSearchParams(window.location.search);

    if (!options.keepPage) {
        params.delete('page');
    }

    for (const [key, value] of Object.entries(changes)) {
        const text = Array.isArray(value) ? value.join(',') : value;

        if (text === null || text === undefined || text === '') {
            params.delete(key);
        } else {
            params.set(key, String(text));
        }
    }

    const search = params.toString();

    router.get(
        `${window.location.pathname}${search ? `?${search}` : ''}`,
        {},
        {
            only: options.only,
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}
