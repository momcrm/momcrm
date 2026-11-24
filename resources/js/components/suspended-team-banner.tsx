import { usePage } from '@inertiajs/react';

/**
 * Tells the team's members why nothing they change is saved: the workspace is
 * suspended, so it can be read but not changed.
 */
export default function SuspendedTeamBanner() {
    const { auth } = usePage().props;

    if (!auth.currentTeam?.is_suspended) {
        return null;
    }

    return (
        <div
            role="status"
            data-test="suspended-team-banner"
            className="mx-4 mt-2 rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-2 text-sm md:mx-6"
        >
            This workspace is suspended. You can still look around, but nothing
            in it can be changed.
        </div>
    );
}
