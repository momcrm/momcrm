import { Head, Link } from '@inertiajs/react';
import { Check, ChevronRight } from 'lucide-react';
import { login } from '@/routes/portal';
import { store } from '@/routes/portal/choose';
import type { PortalChoice } from '@/types';

type Props = {
    choices: PortalChoice[];
    /** Who they are signed in as now, when switching. */
    currentId: string | null;
};

/**
 * "Which customer?": for an email that's a portal contact at several
 * customers, perhaps of several providers.
 */
export default function PortalChoose({ choices, currentId }: Props) {
    return (
        <>
            <Head title="Choose a customer" />

            <p className="-mt-2 text-center text-sm text-muted-foreground">
                Your email can see the portal for more than one customer. Which
                one do you want?
            </p>

            <ul className="divide-y overflow-hidden rounded-lg border">
                {choices.map((choice) => (
                    <li key={choice.id}>
                        <Link
                            href={store(choice.id)}
                            as="button"
                            className="flex w-full items-center gap-3 px-3 py-2.5 text-left hover:bg-accent"
                        >
                            <span className="min-w-0 flex-1">
                                <span className="block truncate text-sm font-medium">
                                    {choice.customer}
                                </span>
                                <span className="block truncate text-xs text-muted-foreground">
                                    {choice.team} · as {choice.name}
                                </span>
                            </span>
                            {choice.id === currentId ? (
                                <Check className="size-4 text-muted-foreground" />
                            ) : (
                                <ChevronRight className="size-4 text-muted-foreground" />
                            )}
                        </Link>
                    </li>
                ))}
            </ul>

            {currentId === null && (
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
    title: 'Choose a customer',
};
