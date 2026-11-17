import { Head, router, setLayoutProps } from '@inertiajs/react';
import { toast } from 'sonner';
import ConfirmDelete from '@/components/confirm-delete';
import { ObjectIcon } from '@/components/custom-objects/object-icon';
import { timeAgo } from '@/lib/format';
import { destroy, index, show, update } from '@/routes/records';
import type { Abilities, CustomObject, RecordItem } from '@/types';

/**
 * One record of a custom object. Its name is edited in place and saves as
 * soon as it changes.
 */
export default function ObjectRecord({
    object,
    record,
    can,
}: {
    object: CustomObject;
    record: RecordItem;
    can: Abilities;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: object.name_plural, href: index(object.id) },
            { title: record.name, href: show([object.id, record.id]) },
        ],
    });

    const save = (fields: Record<string, unknown>) =>
        router.patch(update.url([object.id, record.id]), fields as never, {
            preserveScroll: true,
            onError: (errors) => {
                const message = Object.values(errors)[0];

                if (message) {
                    toast.error(message);
                }
            },
        });

    return (
        <>
            <Head title={`${record.name} · ${object.name_plural}`} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 md:p-6">
                <header className="flex items-start gap-3">
                    <ObjectIcon icon={object.icon} hue={object.hue} size="lg" />
                    <div className="min-w-0 flex-1">
                        <p className="text-xs text-muted-foreground">
                            {object.name} #{record.number}
                        </p>
                        <h1>
                            <input
                                key={record.name}
                                defaultValue={record.name}
                                aria-label="Name"
                                maxLength={255}
                                disabled={!can.update}
                                onBlur={(event) => {
                                    const name = event.target.value.trim();

                                    if (name && name !== record.name) {
                                        save({ name });
                                    } else {
                                        event.target.value = record.name;
                                    }
                                }}
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter') {
                                        event.currentTarget.blur();
                                    }

                                    if (event.key === 'Escape') {
                                        event.currentTarget.value = record.name;
                                        event.currentTarget.blur();
                                    }
                                }}
                                className="-ml-1.5 h-9 w-full rounded-md border border-transparent bg-transparent px-1.5 text-xl font-semibold tracking-tight hover:border-border focus:border-ring focus:outline-none disabled:hover:border-transparent"
                            />
                        </h1>
                    </div>
                    {can.delete && (
                        <ConfirmDelete
                            action={destroy.form([object.id, record.id])}
                            title={`Delete ${record.name}?`}
                            description="It will be gone for good, and can't be restored."
                        />
                    )}
                </header>

                <section className="rounded-lg border bg-card p-4 shadow-xs">
                    <dl className="grid grid-cols-[7.5rem_minmax(0,1fr)] items-center gap-x-3 gap-y-2 text-sm">
                        <dt className="text-muted-foreground">Added</dt>
                        <dd>{timeAgo(record.created_at)}</dd>
                        <dt className="text-muted-foreground">Updated</dt>
                        <dd>{timeAgo(record.updated_at)}</dd>
                    </dl>
                </section>
            </div>
        </>
    );
}
