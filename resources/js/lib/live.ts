import { echo } from '@inertiajs/react/echo';
import { configureEcho } from '@laravel/echo-react';

/**
 * Whether this build connects to a Reverb server, so pages update the moment
 * something changes. Without one, the few pages that need fresh data poll.
 */
export const isLive = Boolean(import.meta.env.VITE_REVERB_APP_KEY);

/**
 * How live props hear about changes: through Echo, connected to Reverb with
 * the VITE_REVERB_* settings, or not at all when the build has none.
 */
export function liveUpdates() {
    if (!isLive) {
        return undefined;
    }

    configureEcho({ broadcaster: 'reverb' });

    return echo();
}
