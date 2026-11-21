import { Head, Link } from '@inertiajs/react';
import { Check, ChevronRight } from 'lucide-react';
import { login } from '@/routes';
import { workspace as chooseWorkspace } from '@/routes/choose';
import { store } from '@/routes/portal/choose';
import type { PortalChoice } from '@/types';

type Props = {
    choices: PortalChoice[];
    /** Their staff account, when the address is staff's too. */
    workspace: { name: string; email: string } | null;
    /** The contact they are signed in as now, when switching. */
    currentId: string | null;
    /** Whether they are signed in as staff now. */
    isInWorkspace: boolean;
};

const rowClassName =
    'flex w-full items-center gap-3 px-3 py-2.5 text-left hover:bg-accent';

function RowEnd({ isCurrent }: { isCurrent: boolean }) {
    return isCurrent ? (
        <Check className="size-4 text-muted-foreground" />
    ) : (
        <ChevronRight className="size-4 text-muted-foreground" />
    );
}

/**
 * "Where to?": for an address that can sign in to its staff workspace and a
 * customer portal, or to the portals of several customers.
 */
export default function PortalChoose({
    choices,
    workspace,
    currentId,
    isInWorkspace,
}: Props) {
    const isSignedIn = isInWorkspace || currentId !== null;

    return (
        <>
            <Head title="Where to?" />

            <p className="-mt-2 text-center text-sm text-muted-foreground">
                Your email can sign in to more than one place. Where do you want
                to go?
            </p>

            <ul className="divide-y overflow-hidden rounded-lg border">
                {workspace && (
                    <li>
                        <Link
                            href={chooseWorkspace()}
                            as="button"
                            className={rowClassName}
                        >
                            <span className="min-w-0 flex-1">
                                <span className="block truncate text-sm font-medium">
                                    Your workspace
                                </span>
                                <span className="block truncate text-xs text-muted-foreground">
                                    Staff · as {workspace.name}
                                </span>
                            </span>
                            <RowEnd isCurrent={isInWorkspace} />
                        </Link>
                    </li>
                )}
                {choices.map((choice) => (
                    <li key={choice.id}>
                        <Link
                            href={store(choice.id)}
                            as="button"
                            className={rowClassName}
                        >
                            <span className="min-w-0 flex-1">
                                <span className="block truncate text-sm font-medium">
                                    {choice.customer}
                                </span>
                                <span className="block truncate text-xs text-muted-foreground">
                                    Customer portal · {choice.team} · as{' '}
                                    {choice.name}
                                </span>
                            </span>
                            <RowEnd isCurrent={choice.id === currentId} />
                        </Link>
                    </li>
                ))}
            </ul>

            {!isSignedIn && (
                <Link
                    href={login()}
                    className="text-center text-sm text-muted-foreground underline-offset-4 hover:underline"
                >
                    Use a different email
                </Link>
            )}
        </>
    );
}

PortalChoose.layout = {
    title: 'Where to?',
};
