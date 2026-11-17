import { Form, Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useCreateContact } from '@/components/contacts/create-contact-dialog';
import { useCreateCustomer } from '@/components/customers/create-customer-dialog';
import { useCreateTicket } from '@/components/tickets/create-ticket-dialog';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import DismissGettingStartedController from '@/actions/App/Onboarding/Controllers/DismissGettingStartedController';
import type { GettingStarted } from '@/types';

function DismissButton({ label }: { label: string }) {
    return (
        <Form {...DismissGettingStartedController.form()}>
            {({ processing }) => (
                <Button
                    variant="ghost"
                    size="sm"
                    disabled={processing}
                    data-test="dismiss-getting-started-button"
                >
                    {label}
                </Button>
            )}
        </Form>
    );
}

/**
 * The first-run checklist on the dashboard, Notion style: each step links to
 * where it gets done and ticks itself off once it is.
 */
export default function GettingStartedChecklist({
    gettingStarted,
    preferredName,
}: {
    gettingStarted: GettingStarted;
    preferredName: string;
}) {
    const { openCreateTicket } = useCreateTicket();
    const { openCreateCustomer } = useCreateCustomer();
    const { openCreateContact } = useCreateContact();
    const openers: Record<string, () => void> = {
        customer: () => openCreateCustomer(),
        contact: () => openCreateContact(),
        ticket: () => openCreateTicket(),
    };
    const { steps, completed, total } = gettingStarted;
    const allDone = completed === total;

    if (allDone) {
        return (
            <section className="flex flex-wrap items-center justify-between gap-4 rounded-xl border p-6">
                <div className="space-y-1">
                    <h2 className="text-lg font-semibold">You're all set.</h2>
                    <p className="text-sm text-muted-foreground">
                        That's the setup done. Everything your team adds from
                        here shows up in the activity feed.
                    </p>
                </div>
                <DismissButton label="Hide checklist" />
            </section>
        );
    }

    return (
        <section className="space-y-4 rounded-xl border p-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="space-y-1">
                    <h2 className="text-lg font-semibold">
                        Welcome to MomCRM, {preferredName}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Your workspace is ready. A few quick steps and your team
                        will have everything in one place.
                    </p>
                </div>
                <DismissButton label="I'll explore on my own" />
            </div>

            <div className="space-y-2">
                <p className="text-sm font-medium">
                    Get set up — {completed} of {total} done
                </p>
                <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        className="h-full rounded-full bg-primary transition-all"
                        style={{ width: `${(completed / total) * 100}%` }}
                    />
                </div>
            </div>

            <ol className="divide-y rounded-lg border">
                {steps.map((step) => (
                    <li
                        key={step.key}
                        className="flex items-center justify-between gap-4 px-4 py-3"
                    >
                        <div className="flex min-w-0 items-start gap-3">
                            <span
                                className={cn(
                                    'mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border',
                                    step.done &&
                                        'border-primary bg-primary text-primary-foreground',
                                )}
                                aria-hidden
                            >
                                {step.done && <Check className="size-3.5" />}
                            </span>
                            <div className="min-w-0">
                                <p
                                    className={cn(
                                        'font-medium',
                                        step.done &&
                                            'text-muted-foreground line-through',
                                    )}
                                >
                                    {step.title}
                                </p>
                                {!step.done && (
                                    <p className="text-sm text-muted-foreground">
                                        {step.description}
                                    </p>
                                )}
                            </div>
                        </div>
                        {!step.done &&
                            (step.key in openers ? (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={openers[step.key]}
                                >
                                    {step.action}
                                </Button>
                            ) : (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={step.href}>{step.action}</Link>
                                </Button>
                            ))}
                    </li>
                ))}
            </ol>
        </section>
    );
}
