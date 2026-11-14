import { Head, Link, usePage } from '@inertiajs/react';
import { Building2, ShieldCheck, Ticket } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

const points = [
    {
        icon: Building2,
        title: 'Know everyone.',
        body: 'Each customer with its contacts, locations, assets and services in one view.',
    },
    {
        icon: Ticket,
        title: 'Never drop a ticket.',
        body: 'Tickets with owners, priorities and statuses, so nothing waits unseen.',
    },
    {
        icon: ShieldCheck,
        title: 'Built for teams.',
        body: 'Shared workspaces, a full audit trail and passkey sign-in.',
    },
];

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="The open-source CRM" />

            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="mx-auto flex w-full max-w-5xl items-center justify-between p-6">
                    <Link href="/" className="flex items-center gap-2">
                        <AppLogoIcon className="size-7 fill-current" />
                        <span className="font-semibold">MomCRM</span>
                    </Link>

                    <nav className="flex items-center gap-2">
                        {auth.user ? (
                            <Button asChild>
                                <Link href={dashboard()}>Go to dashboard</Link>
                            </Button>
                        ) : (
                            <Button asChild variant="ghost">
                                <Link href={login()}>Log in</Link>
                            </Button>
                        )}
                    </nav>
                </header>

                <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center gap-16 px-6 py-12">
                    <section className="flex max-w-2xl flex-col gap-6">
                        <h1 className="text-4xl font-semibold tracking-tight md:text-5xl">
                            Every client, every ticket, one place.
                        </h1>
                        <p className="text-lg text-muted-foreground">
                            MomCRM keeps your customers, contacts, tickets and
                            invoices together, so your team always knows what
                            each client needs next. Free and open source.
                        </p>

                        {!auth.user && (
                            <div className="flex flex-wrap gap-3">
                                {canRegister && (
                                    <Button asChild size="lg">
                                        <Link href={register()}>
                                            Create your free account
                                        </Link>
                                    </Button>
                                )}
                                <Button asChild size="lg" variant="outline">
                                    <Link href={login()}>Log in</Link>
                                </Button>
                            </div>
                        )}
                    </section>

                    <section className="grid gap-6 md:grid-cols-3">
                        {points.map((point) => (
                            <div
                                key={point.title}
                                className="flex flex-col gap-2 rounded-xl border p-6"
                            >
                                <point.icon className="size-5 text-muted-foreground" />
                                <h2 className="font-medium">{point.title}</h2>
                                <p className="text-sm text-muted-foreground">
                                    {point.body}
                                </p>
                            </div>
                        ))}
                    </section>
                </main>

                <footer className="mx-auto w-full max-w-5xl p-6 text-sm text-muted-foreground">
                    Open source under the MIT licence. Self-host it or use ours.
                </footer>
            </div>
        </>
    );
}
