import { Form, Head, router, setLayoutProps } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import DataTable, { dataTableColumns } from '@/components/data-table';
import EmptyState from '@/components/empty-state';
import FormField from '@/components/form-field';
import PageHeader from '@/components/page-header';
import Pager from '@/components/pager';
import SearchInput from '@/components/search-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { timeAgo } from '@/lib/format';
import { visitWithQuery } from '@/lib/query';
import { index, show, store } from '@/routes/records';
import type { Abilities, CustomObject, Paginated, RecordItem } from '@/types';

const RELOAD = ['records', 'filters'];

const column = dataTableColumns<RecordItem>();

const columns = column.columns([
    column.accessor('number', {
        header: '#',
        enableSorting: true,
        meta: { className: 'w-16' },
        cell: (info) => (
            <span className="text-muted-foreground tabular-nums">
                {info.getValue()}
            </span>
        ),
    }),
    column.accessor('name', {
        header: 'Name',
        enableSorting: true,
        cell: (info) => <span className="font-medium">{info.getValue()}</span>,
    }),
    column.accessor('updated_at', {
        header: 'Updated',
        enableSorting: true,
        meta: { className: 'w-32' },
        cell: (info) => (
            <span className="text-muted-foreground">
                {timeAgo(info.getValue())}
            </span>
        ),
    }),
]);

/**
 * One custom object's records: searchable by name, sortable, and each opening
 * its own page.
 */
export default function ObjectRecords({
    object,
    records,
    filters,
    can,
}: {
    object: CustomObject;
    records: Paginated<RecordItem>;
    filters: { search: string | null; sort: string; direction: 'asc' | 'desc' };
    can: Abilities;
}) {
    setLayoutProps({
        breadcrumbs: [{ title: object.name_plural, href: index(object.id) }],
    });

    const [creating, setCreating] = useState(false);
    const noun = object.name.toLowerCase();
    const add = can.create && (
        <Button onClick={() => setCreating(true)}>
            <Plus />
            Add {noun}
        </Button>
    );

    return (
        <>
            <Head title={object.name_plural} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={object.name_plural}
                    description={object.description}
                    actions={add}
                />

                {(records.total > 0 || filters.search) && (
                    <SearchInput
                        value={filters.search}
                        placeholder={`Search ${object.name_plural.toLowerCase()}`}
                        only={RELOAD}
                    />
                )}

                {records.data.length === 0 ? (
                    filters.search ? (
                        <EmptyState
                            title={`No matches for "${filters.search}"`}
                            description="Check the spelling or try a shorter search."
                        />
                    ) : (
                        <EmptyState
                            title={`No ${object.name_plural.toLowerCase()} yet`}
                            description={
                                object.description ??
                                `Add your first ${noun} to start the list.`
                            }
                            action={add || undefined}
                        />
                    )
                ) : (
                    <>
                        <DataTable
                            columns={columns}
                            data={records.data}
                            getRowId={(record) => record.id}
                            sort={{
                                column: filters.sort,
                                direction: filters.direction,
                            }}
                            onSortChange={({ column: sort, direction }) =>
                                visitWithQuery(
                                    { sort, direction },
                                    { only: RELOAD },
                                )
                            }
                            onRowClick={(record) =>
                                router.visit(show.url([object.id, record.id]))
                            }
                        />
                        <Pager page={records} only="records" />
                    </>
                )}
            </div>

            <Dialog open={creating} onOpenChange={setCreating}>
                <DialogContent>
                    <DialogTitle>Add {noun}</DialogTitle>
                    <DialogDescription>
                        Give it a name. You can fill in the rest on its page.
                    </DialogDescription>
                    <Form {...store.form(object.id)} className="space-y-5">
                        {({ errors, processing }) => (
                            <>
                                <FormField
                                    id="record-name"
                                    label="Name"
                                    error={errors.name}
                                >
                                    <Input
                                        id="record-name"
                                        name="name"
                                        maxLength={255}
                                        required
                                        autoFocus
                                    />
                                </FormField>
                                <DialogFooter className="gap-2">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        onClick={() => setCreating(false)}
                                    >
                                        Cancel
                                    </Button>
                                    <Button disabled={processing}>
                                        Add {noun}
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>
        </>
    );
}
