import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';

/**
 * A faint, blurred sketch of the MomCRM app behind the sign-in card, the way
 * Notion shows its workspace behind its sign-up box. Purely decorative.
 */
function WorkspaceBackdrop() {
    return (
        <div
            aria-hidden
            className="pointer-events-none fixed inset-0 flex blur-[2px] select-none"
        >
            <div className="hidden w-56 shrink-0 flex-col gap-3 border-r bg-muted/60 p-4 md:flex">
                <div className="mb-4 h-5 w-32 rounded bg-foreground/10" />
                {Array.from({ length: 9 }, (_, index) => (
                    <div
                        key={index}
                        className="h-3 rounded bg-foreground/10"
                        style={{ width: `${55 + ((index * 17) % 35)}%` }}
                    />
                ))}
            </div>
            <div className="flex flex-1 flex-col">
                <div className="h-40 bg-primary/10" />
                <div className="flex flex-col gap-4 p-10">
                    <div className="h-7 w-72 rounded bg-foreground/10" />
                    <div className="h-3 w-48 rounded bg-foreground/10" />
                    <div className="mt-6 grid max-w-3xl grid-cols-3 gap-4">
                        {Array.from({ length: 3 }, (_, index) => (
                            <div
                                key={index}
                                className="h-28 rounded-lg border bg-background"
                            />
                        ))}
                    </div>
                    <div className="mt-4 h-10 max-w-3xl rounded bg-foreground/5" />
                </div>
            </div>
            <div className="absolute inset-0 bg-foreground/25 dark:bg-background/60" />
        </div>
    );
}

export default function AuthBackdropLayout({
    children,
    title,
    description,
}: {
    children: ReactNode;
    title?: string;
    description?: string;
}) {
    return (
        <div className="relative flex min-h-svh items-center justify-center bg-background p-4">
            <WorkspaceBackdrop />

            <main className="relative w-full max-w-sm rounded-xl border bg-background p-8 shadow-xl">
                <div className="flex flex-col gap-6">
                    <div className="flex flex-col items-center gap-3 text-center">
                        <Link href={home()} aria-label="MomCRM home">
                            <AppLogoIcon className="size-9 fill-current text-[var(--foreground)] dark:text-white" />
                        </Link>
                        {title && (
                            <h1 className="text-lg leading-snug font-semibold">
                                {title}
                            </h1>
                        )}
                        {description && (
                            <p className="-mt-2 text-base text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                    {children}
                </div>
            </main>
        </div>
    );
}
