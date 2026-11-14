import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';

/**
 * A full-screen, sidebar-free frame for the first-run onboarding steps.
 */
export default function OnboardingLayout({
    children,
}: {
    children: ReactNode;
}) {
    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="flex items-center gap-2 p-6 md:px-10">
                <AppLogoIcon className="size-7 fill-current text-[var(--foreground)] dark:text-white" />
                <span className="font-semibold">MomCRM</span>
            </header>

            <main className="flex flex-1 justify-center px-6 pb-16 md:items-center">
                <div className="w-full max-w-xl">{children}</div>
            </main>
        </div>
    );
}
