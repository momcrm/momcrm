import { Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

/**
 * Tells the team's members why nothing they change is saved: the workspace is
 * suspended, so it can be read but not changed. When there is something to
 * do about it, such as subscribing on a hosted install, it links there.
 */
export default function SuspendedTeamBanner() {
    const { auth, suspendedTeamAction } = usePage().props;

    if (!auth.currentTeam?.is_suspended) {
        return null;
    }

    return (
        <div
            role="status"
            data-test="suspended-team-banner"
            className="mx-4 mt-2 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-2 text-sm md:mx-6"
        >
            <span>
                This workspace is suspended. You can still look around, but
                nothing in it can be changed.
            </span>
            {suspendedTeamAction && (
                <Button variant="outline" size="sm" asChild>
                    <Link href={suspendedTeamAction.href}>
                        {suspendedTeamAction.label}
                    </Link>
                </Button>
            )}
        </div>
    );
}
