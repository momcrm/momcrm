import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import Attachments from '@/components/attachments';
import { TabHeader } from '@/components/customers/workspace-tab';
import DataTable, { dataTableColumns } from '@/components/data-table';
import type { DataTableSort } from '@/components/data-table';
import EmptyState from '@/components/empty-state';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import SoftBadge from '@/components/soft-badge';
import StatusPill from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { addDays, today } from '@/lib/dates';
import { visitWithQuery } from '@/lib/query';
import { contracts as contractsTab } from '@/routes/customers';
import { destroy, store, update } from '@/routes/contracts';
import { store as storeDocuments } from '@/routes/contracts/attachments';
import type {
    Abilities,
    AttachmentItem,
    ContractItem,
    ContractStatus,
    CustomerWorkspace,
    Option,
    Tone,
} from '@/types';

type ContractRow = ContractItem & { documents: AttachmentItem[] };

type Filters = {
    status: ContractStatus | null;
    type: string | null;
    sort: string;
    direction: 'asc' | 'desc';
};

const RELOAD = ['contracts', 'filters', 'workspace'];

const ENDS_SOON_DAYS = 30;

const statusTones: Record<ContractStatus, Tone> = {
    draft: 'neutral',
    pending: 'warning',
    active: 'success',
    expired: 'neutral',
    cancelled: 'destructive',
};

const dayFormatter = new Intl.DateTimeFormat(undefined, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

function formatDate(day: string | null): string {
    return day ? dayFormatter.format(new Date(`${day}T00:00:00`)) : '—';
}

/**
 * Whether an active contract's end date falls within the next 30 days.
 */
function endsSoon(contract: ContractItem): boolean {
    return (
        contract.status === 'active' &&
        contract.ends_on !== null &&
        contract.ends_on >= today() &&
        contract.ends_on <= addDays(today(), ENDS_SOON_DAYS)
    );
}

function buildColumns(statuses: Option[]) {
    const column = dataTableColumns<ContractRow>();
    const statusLabel = (status: string) =>
        statuses.find((option) => option.value === status)?.label ?? status;

    return column.columns([
        column.accessor('title', {
            header: 'Title',
            enableSorting: true,
            cell: (info) => (
                <span className="flex min-w-0 items-center gap-2">
                    <span className="truncate font-medium">
                        {info.getValue()}
                    </span>
                    {info.row.original.documents.length > 0 && (
                        <span className="shrink-0 text-xs text-subtle-foreground">
                            {info.row.original.documents.length} doc
                            {info.row.original.documents.length === 1
                                ? ''
                                : 's'}
                        </span>
                    )}
                </span>
            ),
        }),
        column.accessor('type', {
            header: 'Type',
            meta: { className: 'w-20' },
            cell: (info) => (
                <span className="font-mono text-xs text-soft-foreground uppercase">
                    {info.getValue()}
                </span>
            ),
        }),
        column.accessor('status', {
            header: 'Status',
            meta: { className: 'w-44' },
            cell: (info) => (
                <span className="flex items-center gap-1.5">
                    <StatusPill tone={statusTones[info.getValue()]}>
                        {statusLabel(info.getValue())}
                    </StatusPill>
                    {endsSoon(info.row.original) && (
                        <SoftBadge tone="warning">Ends soon</SoftBadge>
                    )}
                </span>
            ),
        }),
        column.accessor('starts_on', {
            header: 'Starts',
            enableSorting: true,
            meta: { className: 'hidden md:table-cell w-28' },
            cell: (info) => (
                <span className="text-muted-foreground tabular-nums">
                    {formatDate(info.getValue())}
                </span>
            ),
        }),
        column.accessor('ends_on', {
            header: 'Ends',
            enableSorting: true,
            meta: { className: 'w-28' },
            cell: (info) => (
                <span className="text-muted-foreground tabular-nums">
                    {formatDate(info.getValue())}
                </span>
            ),
        }),
    ]);
}

function ContractDialog({
    customerId,
    contract,
    types,
    statuses,
    contractCan,
    documentCan,
    onClose,
}: {
    customerId: string;
    contract: ContractRow | null;
    types: Option[];
    statuses: Option[];
    contractCan: Abilities;
    documentCan: Abilities;
    onClose: () => void;
}) {
    const [fields, setFields] = useState({
        title: contract?.title ?? '',
        type: contract?.type ?? 'msa',
        status: contract?.status ?? 'draft',
        signed_on: contract?.signed_on ?? '',
        starts_on: contract?.starts_on ?? '',
        ends_on: contract?.ends_on ?? '',
        purchase_order: contract?.purchase_order ?? '',
        description: contract?.description ?? '',
        notes: contract?.notes ?? '',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSaving, setIsSaving] = useState(false);
    const canEdit = contract ? Boolean(contractCan.update) : true;
    const set =
        (field: keyof typeof fields) =>
        (event: { target: { value: string } }) =>
            setFields((current) => ({
                ...current,
                [field]: event.target.value,
            }));

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();
        const data = Object.fromEntries(
            Object.entries(fields).map(([key, value]) => [key, value || null]),
        );
        const options = {
            only: RELOAD,
            preserveScroll: true,
            onStart: () => setIsSaving(true),
            onFinish: () => setIsSaving(false),
            onError: setErrors,
            onSuccess: onClose,
        };

        if (contract) {
            router.put(update.url(contract.id), data, options);
        } else {
            router.post(store.url(customerId), data, options);
        }
    };

    return (
        <Dialog open onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className="max-h-[88vh] overflow-y-auto sm:max-w-xl">
                <form onSubmit={onSubmit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>
                            {contract ? contract.title : 'New contract'}
                        </DialogTitle>
                    </DialogHeader>

                    <fieldset disabled={!canEdit} className="space-y-4">
                        <FormField
                            id="title"
                            label="Title"
                            error={errors.title}
                        >
                            <Input
                                id="title"
                                value={fields.title}
                                onChange={set('title')}
                                placeholder="Managed services agreement"
                                required
                                autoFocus
                            />
                        </FormField>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField
                                id="type"
                                label="Type"
                                error={errors.type}
                            >
                                <NativeSelect
                                    id="type"
                                    value={fields.type}
                                    onChange={set('type')}
                                >
                                    {types.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            <FormField
                                id="status"
                                label="Status"
                                error={errors.status}
                            >
                                <NativeSelect
                                    id="status"
                                    value={fields.status}
                                    onChange={set('status')}
                                >
                                    {statuses.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <FormField
                                id="signed_on"
                                label="Signed"
                                error={errors.signed_on}
                            >
                                <Input
                                    id="signed_on"
                                    type="date"
                                    value={fields.signed_on}
                                    onChange={set('signed_on')}
                                />
                            </FormField>
                            <FormField
                                id="starts_on"
                                label="Starts"
                                error={errors.starts_on}
                            >
                                <Input
                                    id="starts_on"
                                    type="date"
                                    value={fields.starts_on}
                                    onChange={set('starts_on')}
                                />
                            </FormField>
                            <FormField
                                id="ends_on"
                                label="Ends"
                                error={errors.ends_on}
                            >
                                <Input
                                    id="ends_on"
                                    type="date"
                                    value={fields.ends_on}
                                    onChange={set('ends_on')}
                                />
                            </FormField>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField
                                id="purchase_order"
                                label="Purchase order"
                                error={errors.purchase_order}
                            >
                                <Input
                                    id="purchase_order"
                                    value={fields.purchase_order}
                                    onChange={set('purchase_order')}
                                />
                            </FormField>
                            <FormField
                                id="description"
                                label="Description"
                                error={errors.description}
                            >
                                <Input
                                    id="description"
                                    value={fields.description}
                                    onChange={set('description')}
                                    maxLength={255}
                                />
                            </FormField>
                        </div>
                        <FormField
                            id="notes"
                            label="Notes"
                            error={errors.notes}
                        >
                            <Textarea
                                id="notes"
                                rows={3}
                                value={fields.notes}
                                onChange={set('notes')}
                            />
                        </FormField>
                    </fieldset>

                    {contract && (
                        <section className="space-y-2">
                            <h3 className="text-sm font-medium">Documents</h3>
                            <Attachments
                                attachments={contract.documents}
                                uploadUrl={storeDocuments.url(contract.id)}
                                reload={RELOAD}
                                can={documentCan}
                            />
                        </section>
                    )}

                    <DialogFooter className="sm:justify-between">
                        <div>
                            {contract && contractCan.delete && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="text-destructive"
                                    onClick={() =>
                                        router.delete(
                                            destroy.url(contract.id),
                                            {
                                                only: RELOAD,
                                                preserveScroll: true,
                                                onSuccess: onClose,
                                            },
                                        )
                                    }
                                >
                                    Delete
                                </Button>
                            )}
                        </div>
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={onClose}
                            >
                                {canEdit ? 'Cancel' : 'Close'}
                            </Button>
                            {canEdit && (
                                <Button type="submit" disabled={isSaving}>
                                    {contract ? 'Save' : 'Add contract'}
                                </Button>
                            )}
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function CustomerContracts({
    workspace,
    contracts,
    filters,
    types,
    statuses,
    contractCan,
    documentCan,
}: {
    workspace: CustomerWorkspace;
    contracts: ContractRow[];
    filters: Filters;
    types: Option[];
    statuses: Option[];
    contractCan: Abilities;
    documentCan: Abilities;
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Contracts',
        href: contractsTab(customer.id),
    });
    const [openId, setOpenId] = useState<string | null>(null);
    const [isCreating, setIsCreating] = useState(false);
    const open = contracts.find((contract) => contract.id === openId) ?? null;
    const isFiltered = Boolean(filters.status || filters.type);
    const columns = buildColumns(statuses);
    const add = contractCan.create && (
        <Button size="sm" variant="outline" onClick={() => setIsCreating(true)}>
            New contract
        </Button>
    );

    return (
        <>
            <Head title={`${customer.name} · Contracts`} />
            <TabHeader
                title="Contracts"
                description="Agreements with this customer and their signed copies."
                action={add}
            />

            {(contracts.length > 0 || isFiltered) && (
                <div className="mb-4 flex flex-wrap items-center gap-2">
                    <NativeSelect
                        aria-label="Status"
                        value={filters.status ?? ''}
                        onChange={(event) =>
                            visitWithQuery(
                                { status: event.target.value },
                                { only: RELOAD },
                            )
                        }
                        className="h-8 w-auto text-[13px]"
                    >
                        <option value="">Any status</option>
                        {statuses.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </NativeSelect>
                    <NativeSelect
                        aria-label="Type"
                        value={filters.type ?? ''}
                        onChange={(event) =>
                            visitWithQuery(
                                { type: event.target.value },
                                { only: RELOAD },
                            )
                        }
                        className="h-8 w-auto text-[13px]"
                    >
                        <option value="">Any type</option>
                        {types.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </NativeSelect>
                </div>
            )}

            {contracts.length > 0 ? (
                <DataTable
                    columns={columns}
                    data={contracts}
                    getRowId={(contract) => contract.id}
                    sort={{
                        column: filters.sort,
                        direction: filters.direction,
                    }}
                    onSortChange={(sort: DataTableSort) =>
                        visitWithQuery(
                            {
                                sort: sort.column,
                                direction: sort.direction,
                            },
                            { only: RELOAD },
                        )
                    }
                    onRowClick={(contract) => setOpenId(contract.id)}
                />
            ) : isFiltered ? (
                <EmptyState
                    title="No matching contracts"
                    description="Try another status or type."
                />
            ) : (
                <EmptyState
                    title="No contracts yet"
                    description={`Record your MSAs, NDAs and SLAs with ${customer.name}, with the signed copies attached.`}
                    action={add}
                />
            )}

            {(open || isCreating) && (
                <ContractDialog
                    key={open?.id ?? 'new'}
                    customerId={customer.id}
                    contract={open}
                    types={types}
                    statuses={statuses}
                    contractCan={contractCan}
                    documentCan={documentCan}
                    onClose={() => {
                        setOpenId(null);
                        setIsCreating(false);
                    }}
                />
            )}
        </>
    );
}
