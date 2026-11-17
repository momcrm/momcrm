import { Head, router, setLayoutProps } from '@inertiajs/react';
import { Fragment } from 'react';
import { toast } from 'sonner';
import ConfirmDelete from '@/components/confirm-delete';
import { InlineCustomFieldInput } from '@/components/custom-fields/custom-field-input';
import { CustomFieldValueView } from '@/components/custom-fields/custom-fields-form';
import LinkedRecords from '@/components/custom-fields/linked-records';
import { ObjectIcon } from '@/components/custom-objects/object-icon';
import { timeAgo } from '@/lib/format';
import { destroy, index, show, update } from '@/routes/records';
import type {
    Abilities,
    CustomField,
    CustomObject,
    LinkedRecordGroup,
    RecordItem,
} from '@/types';

/**
 * One record of a custom object. Its name and its object's fields are edited
 * in place and save as soon as they change.
 */
export default function ObjectRecord({
    object,
    fields,
    record,
    linkedRecords,
    can,
}: {
    object: CustomObject;
    fields: CustomField[];
    record: RecordItem;
    linkedRecords: LinkedRecordGroup[];
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
                    <dl className="grid grid-cols-[9rem_minmax(0,1fr)] items-center gap-x-3 gap-y-1 text-sm">
                        {fields.map((field) => (
                            <Fragment key={field.id}>
                                <dt
                                    className="truncate text-muted-foreground"
                                    title={field.description ?? undefined}
                                >
                                    {field.name}
                                </dt>
                                <dd className="min-w-0">
                                    {can.update ? (
                                        <InlineCustomFieldInput
                                            field={field}
                                            value={
                                                record.custom_fields[field.id]
                                            }
                                            onChange={(value) =>
                                                save({
                                                    custom_fields: {
                                                        [field.id]: value,
                                                    },
                                                })
                                            }
                                        />
                                    ) : (
                                        <span className="flex h-8 items-center px-2">
                                            <CustomFieldValueView
                                                field={field}
                                                value={
                                                    record.custom_fields[
                                                        field.id
                                                    ]
                                                }
                                            />
                                        </span>
                                    )}
                                </dd>
                            </Fragment>
                        ))}
                        <dt className="text-muted-foreground">Added</dt>
                        <dd className="flex h-8 items-center px-2">
                            {timeAgo(record.created_at)}
                        </dd>
                        <dt className="text-muted-foreground">Updated</dt>
                        <dd className="flex h-8 items-center px-2">
                            {timeAgo(record.updated_at)}
                        </dd>
                    </dl>
                </section>

                <LinkedRecords groups={linkedRecords} />
            </div>
        </>
    );
}
