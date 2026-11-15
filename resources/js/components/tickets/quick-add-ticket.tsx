import { router } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { store } from '@/routes/tickets';
import type { TicketPickerOptions, TicketStatus } from '@/types';

/**
 * Adds a ticket straight into a board column: a title and, unless the board is
 * one project's, the project or customer it's for. Stays open for the next one.
 */
export default function QuickAddTicket({
    status,
    options,
    projectId,
    onClose,
}: {
    status: TicketStatus;
    options: TicketPickerOptions;
    projectId: string | null;
    onClose: () => void;
}) {
    const [title, setTitle] = useState('');
    const [owner, setOwner] = useState(() => {
        try {
            return sessionStorage.getItem('quick-add-owner') ?? '';
        } catch {
            return '';
        }
    });
    const [saving, setSaving] = useState(false);

    const submit = () => {
        const owned = projectId ? `project:${projectId}` : owner;

        if (!title.trim() || saving) {
            return;
        }

        if (!owned) {
            toast.error('Choose the project or customer this ticket is for.');

            return;
        }

        const [kind, id] = owned.split(':');

        router.post(
            store.url(),
            {
                title: title.trim(),
                status,
                quick: 1,
                [kind === 'project' ? 'project_id' : 'customer_id']: id,
            },
            {
                only: ['tickets', 'sidebarCounts'],
                preserveScroll: true,
                preserveState: true,
                onStart: () => setSaving(true),
                onFinish: () => setSaving(false),
                onSuccess: () => setTitle(''),
                onError: (errors) =>
                    toast.error(
                        Object.values(errors)[0] ?? 'Could not add the ticket.',
                    ),
            },
        );
    };

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                submit();
            }}
            onKeyDown={(event) => event.key === 'Escape' && onClose()}
            className="mt-1 space-y-2 rounded-lg border bg-card p-2 shadow-xs"
        >
            <textarea
                autoFocus
                rows={2}
                value={title}
                onChange={(event) => setTitle(event.target.value)}
                onKeyDown={(event) => {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault();
                        submit();
                    }
                }}
                placeholder="Ticket title"
                aria-label="Ticket title"
                className="block w-full resize-none bg-transparent text-sm outline-none placeholder:text-subtle-foreground"
            />
            {!projectId && (
                <NativeSelect
                    aria-label="For"
                    value={owner}
                    onChange={(event) => {
                        setOwner(event.target.value);
                        try {
                            sessionStorage.setItem(
                                'quick-add-owner',
                                event.target.value,
                            );
                        } catch {
                            // Storage blocked: the choice lasts until the page reloads.
                        }
                    }}
                    className="h-7 text-xs"
                >
                    <option value="">For which project or customer?</option>
                    {options.projects.length > 0 && (
                        <optgroup label="Projects">
                            {options.projects.map((project) => (
                                <option
                                    key={project.id}
                                    value={`project:${project.id}`}
                                >
                                    {project.name}
                                </option>
                            ))}
                        </optgroup>
                    )}
                    <optgroup label="Customers">
                        {options.customers.map((customer) => (
                            <option
                                key={customer.id}
                                value={`customer:${customer.id}`}
                            >
                                {customer.name}
                            </option>
                        ))}
                    </optgroup>
                </NativeSelect>
            )}
            <div className="flex items-center gap-1.5">
                <Button
                    size="sm"
                    className="h-7"
                    disabled={!title.trim() || saving}
                >
                    Add ticket
                </Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    className="h-7"
                    onClick={onClose}
                >
                    Cancel
                </Button>
            </div>
        </form>
    );
}
