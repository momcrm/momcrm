import { Form, Head, router, setLayoutProps } from '@inertiajs/react';
import { Columns3, Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import {
    CustomFieldInput,
    isFilterableCustomField,
    isSortableCustomField,
} from '@/components/custom-fields/custom-field-input';
import {
    customFieldError,
    CustomFieldValueView,
    hasCustomFieldValue,
} from '@/components/custom-fields/custom-fields-form';
import DataTable, { dataTableColumns } from '@/components/data-table';
import EmptyState from '@/components/empty-state';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
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
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { timeAgo } from '@/lib/format';
import { visitWithQuery } from '@/lib/query';
import { index, show, store } from '@/routes/records';
import type {
    Abilities,
    CustomField,
    CustomFieldValues,
    CustomObject,
    Paginated,
    RecordItem,
} from '@/types';

const RELOAD = ['records', 'filters'];

const column = dataTableColumns<RecordItem>();

const baseColumns = column.columns([
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
 * Which of an object's fields this browser hides from its table. A
 * per-viewer convenience, so it lives in local storage.
 */
function useHiddenFields(objectId: string): [string[], (id: string) => void] {
    const key = `objects.${objectId}.hidden-fields`;
    const [hidden, setHidden] = useState<string[]>(() => {
        try {
            const stored: unknown = JSON.parse(
                window.localStorage.getItem(key) ?? '[]',
            );

            return Array.isArray(stored)
                ? stored.filter((id) => typeof id === 'string')
                : [];
        } catch {
            return [];
        }
    });

    const toggle = (fieldId: string) => {
        const next = hidden.includes(fieldId)
            ? hidden.filter((id) => id !== fieldId)
            : [...hidden, fieldId];

        setHidden(next);

        try {
            window.localStorage.setItem(key, JSON.stringify(next));
        } catch {
            // Private windows can refuse storage; the choice just won't stick.
        }
    };

    return [hidden, toggle];
}

/**
 * One custom object's records, with a column per field: searchable by name,
 * filtered and sorted by its fields, and each opening its own page.
 */
export default function ObjectRecords({
    object,
    fields,
    records,
    filters,
    can,
}: {
    object: CustomObject;
    fields: CustomField[];
    records: Paginated<RecordItem>;
    filters: {
        search: string | null;
        sort: string;
        direction: 'asc' | 'desc';
        /** Filters on the object's fields, by field id. */
        custom_fields: Record<string, string>;
    };
    can: Abilities;
}) {
    setLayoutProps({
        breadcrumbs: [{ title: object.name_plural, href: index(object.id) }],
    });

    const [creating, setCreating] = useState(false);
    const [values, setValues] = useState<CustomFieldValues>({});
    const [hiddenFields, toggleField] = useHiddenFields(object.id);
    const hiddenKey = hiddenFields.join(',');
    const columns = useMemo(
        () => [
            ...baseColumns.slice(0, 2),
            ...fields
                .filter((field) => !hiddenFields.includes(field.id))
                .map((field) =>
                    column.accessor(
                        (record) => record.custom_fields[field.id],
                        {
                            id: `cf:${field.id}`,
                            header: field.name,
                            enableSorting: isSortableCustomField(field),
                            meta: { className: 'w-40' },
                            cell: (info) =>
                                hasCustomFieldValue(field, info.getValue()) ? (
                                    <CustomFieldValueView
                                        field={field}
                                        value={info.getValue()}
                                        labels={
                                            info.row.original
                                                .custom_field_labels
                                        }
                                    />
                                ) : null,
                        },
                    ),
                ),
            ...baseColumns.slice(2),
        ],
        [fields, hiddenKey], // eslint-disable-line react-hooks/exhaustive-deps
    );
    const isFiltered =
        Boolean(filters.search) ||
        Object.keys(filters.custom_fields).length > 0;
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

                {(records.total > 0 || isFiltered) && (
                    <div className="flex flex-wrap items-center gap-2">
                        <SearchInput
                            value={filters.search}
                            placeholder={`Search ${object.name_plural.toLowerCase()}`}
                            only={RELOAD}
                        />
                        {fields.filter(isFilterableCustomField).map((field) => (
                            <NativeSelect
                                key={field.id}
                                aria-label={field.name}
                                value={filters.custom_fields[field.id] ?? ''}
                                onChange={(event) =>
                                    visitWithQuery(
                                        {
                                            [`cf[${field.id}]`]:
                                                event.target.value,
                                        },
                                        { only: RELOAD },
                                    )
                                }
                                className="h-8 w-auto text-[13px]"
                            >
                                <option value="">Any {field.name}</option>
                                {field.type === 'checkbox' ? (
                                    <>
                                        <option value="1">
                                            {field.name}: yes
                                        </option>
                                        <option value="0">
                                            {field.name}: no
                                        </option>
                                    </>
                                ) : (
                                    <>
                                        <option value="none">
                                            No {field.name}
                                        </option>
                                        {field.options.map((option) => (
                                            <option
                                                key={option.id}
                                                value={option.id}
                                            >
                                                {option.name}
                                            </option>
                                        ))}
                                    </>
                                )}
                            </NativeSelect>
                        ))}
                        {fields.length > 0 && (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        <Columns3 />
                                        Columns
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    align="start"
                                    className="w-48"
                                >
                                    {fields.map((field) => (
                                        <DropdownMenuCheckboxItem
                                            key={field.id}
                                            checked={
                                                !hiddenFields.includes(field.id)
                                            }
                                            onSelect={(event) =>
                                                event.preventDefault()
                                            }
                                            onCheckedChange={() =>
                                                toggleField(field.id)
                                            }
                                        >
                                            {field.name}
                                        </DropdownMenuCheckboxItem>
                                    ))}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}
                    </div>
                )}

                {records.data.length === 0 ? (
                    isFiltered ? (
                        <EmptyState
                            title={`No matching ${object.name_plural.toLowerCase()}`}
                            description="Check the spelling, or clear a filter."
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
                <DialogContent className="max-h-[90vh] overflow-y-auto">
                    <DialogTitle>Add {noun}</DialogTitle>
                    <DialogDescription>
                        Only the name is needed. The rest can be filled in later
                        on its page.
                    </DialogDescription>
                    <Form
                        {...store.form(object.id)}
                        transform={(data) => ({
                            ...data,
                            custom_fields: values,
                        })}
                        onSuccess={() => setValues({})}
                        className="space-y-5"
                    >
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
                                {fields.length > 0 && (
                                    <div className="grid gap-5 sm:grid-cols-2">
                                        {fields.map((field) => (
                                            <FormField
                                                key={field.id}
                                                id={`record-field-${field.id}`}
                                                label={field.name}
                                                hint={
                                                    field.description ??
                                                    undefined
                                                }
                                                error={customFieldError(
                                                    errors,
                                                    field.id,
                                                )}
                                            >
                                                <CustomFieldInput
                                                    id={`record-field-${field.id}`}
                                                    field={field}
                                                    value={values[field.id]}
                                                    onChange={(value) =>
                                                        setValues({
                                                            ...values,
                                                            [field.id]: value,
                                                        })
                                                    }
                                                />
                                            </FormField>
                                        ))}
                                    </div>
                                )}
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
