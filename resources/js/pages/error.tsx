import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, home } from '@/routes';

const messages: Record<number, { title: string; description: string }> = {
    403: {
        title: "You don't have access to this",
        description:
            "Your team's access rules don't let you open this page. Ask an owner or admin if you think you should.",
    },
    404: {
        title: "We couldn't find that page",
        description:
            'It may have been moved or deleted, or it belongs to a team you are not in.',
    },
    500: {
        title: 'Something went wrong',
        description:
            'We hit a problem on our side and have been told about it. Try again in a moment.',
    },
    503: {
        title: 'Back soon',
        description:
            "We're doing some maintenance. Please check back in a few minutes.",
    },
};

/**
 * The page shown for not-found, forbidden and server errors: inside the app
 * when someone is signed in, on its own otherwise.
 */
export default function ErrorPage({ status }: { status: number }) {
    const isSignedIn = Boolean(usePage().props.auth?.user);
    const { title, description } = messages[status] ?? messages[500];

    return (
        <>
            <Head title={title} />

            <main
                className={
                    isSignedIn
                        ? 'flex flex-1 items-center justify-center px-4 py-16'
                        : 'flex min-h-svh items-center justify-center bg-background px-4 py-16'
                }
            >
                <div className="flex max-w-md flex-col items-center text-center">
                    {!isSignedIn && (
                        <Link href={home()} aria-label="Home" className="mb-10">
                            <AppLogoIcon className="size-9 fill-current text-foreground" />
                        </Link>
                    )}
                    <p className="font-mono text-sm font-medium text-subtle-foreground">
                        Error {status}
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    <p className="mt-3 text-sm text-balance text-muted-foreground">
                        {description}
                    </p>
                    <div className="mt-8 flex items-center gap-2">
                        <Button
                            variant="outline"
                            onClick={() => window.history.back()}
                        >
                            <ArrowLeft />
                            Go back
                        </Button>
                        <Button asChild>
                            <Link href={isSignedIn ? dashboard() : home()}>
                                {isSignedIn ? 'Go to dashboard' : 'Go home'}
                            </Link>
                        </Button>
                    </div>
                </div>
            </main>
        </>
    );
}
