import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { kindIcons } from '@/components/inbox/notification-line';
import { Checkbox } from '@/components/ui/checkbox';
import {
    edit as editNotificationSettings,
    update as updateNotificationSettings,
} from '@/routes/notification-settings';
import type { InboxKind } from '@/types';

const kinds: { value: InboxKind; label: string; description: string }[] = [
    {
        value: 'new_ticket',
        label: 'New tickets',
        description:
            'A customer raises a ticket by email or in the portal, and nobody is assigned it yet. Sent to its group, or to the whole team when it has none.',
    },
    {
        value: 'mention',
        label: 'Mentions',
        description: 'Someone @mentions you in a comment.',
    },
    {
        value: 'assignment',
        label: 'Assignments',
        description:
            'A ticket is assigned to you, or someone asks you to approve leave or a timesheet.',
    },
    {
        value: 'comment',
        label: 'Comments',
        description:
            'Someone comments on, or a customer replies to, a ticket you are assigned or have starred. Customer replies to a ticket nobody is assigned go to its group too.',
    },
    {
        value: 'update',
        label: 'Updates',
        description:
            'A ticket you watch changes status or is due tomorrow, a project is at risk, or your leave or timesheet is reviewed.',
    },
    {
        value: 'message',
        label: 'Direct messages',
        description:
            'A teammate messages you. Sent a few minutes later, and only if you haven’t read it by then.',
    },
];

/**
 * Which inbox notifications the person is also emailed. Each change saves
 * straight away.
 */
export default function NotificationSettings({
    emailed,
}: {
    emailed: InboxKind[];
}) {
    const toggle = (kind: InboxKind, isOn: boolean) =>
        router.put(
            updateNotificationSettings.url(),
            {
                emailed_notifications: isOn
                    ? [...emailed, kind]
                    : emailed.filter((value) => value !== kind),
            },
            { preserveScroll: true, preserveState: true },
        );

    return (
        <>
            <Head title="Notification settings" />

            <h1 className="sr-only">Notification settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Notifications"
                    description="Everything shows in your inbox. Choose what you are also emailed about."
                />

                <ul className="divide-y rounded-lg border bg-card shadow-xs">
                    {kinds.map((kind) => {
                        const Icon = kindIcons[kind.value];

                        return (
                            <li key={kind.value}>
                                <label className="flex cursor-pointer items-start gap-3 px-4 py-3">
                                    <span className="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                        <Icon className="size-3.5" />
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block text-sm font-medium">
                                            {kind.label}
                                        </span>
                                        <span className="block text-xs text-muted-foreground">
                                            {kind.description}
                                        </span>
                                    </span>
                                    <Checkbox
                                        className="mt-1"
                                        checked={emailed.includes(kind.value)}
                                        onCheckedChange={(checked) =>
                                            toggle(kind.value, checked === true)
                                        }
                                        aria-label={`Email me about ${kind.label.toLowerCase()}`}
                                    />
                                </label>
                            </li>
                        );
                    })}
                </ul>
            </div>
        </>
    );
}

NotificationSettings.layout = {
    breadcrumbs: [
        { title: 'Notification settings', href: editNotificationSettings() },
    ],
};
