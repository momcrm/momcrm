import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import { ProjectIcon, projectIcons } from '@/components/projects/project-meta';
import HuePicker from '@/components/hue-picker';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import StoreProjectController from '@/actions/App/Project/Controllers/StoreProjectController';
import UpdateProjectController from '@/actions/App/Project/Controllers/UpdateProjectController';
import { cn } from '@/lib/utils';
import type { ProjectFormOptions, ProjectListItem } from '@/types';

/**
 * Creating a project, or editing one when `project` is given, in a dialog.
 */
export default function ProjectFormDialog({
    open,
    onOpenChange,
    options,
    project,
    canChangeVisibility = true,
    canManageMembers = true,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    options: ProjectFormOptions;
    project?: ProjectListItem;
    /** Without these, the fields are left out so the values stay as they are. */
    canChangeVisibility?: boolean;
    canManageMembers?: boolean;
}) {
    const [hue, setHue] = useState<number | null | undefined>(
        project ? project.hue : undefined,
    );
    const [icon, setIcon] = useState(project?.icon ?? 'folder');
    const [memberIds, setMemberIds] = useState<string[]>(
        project?.members.map((member) => member.id) ?? [],
    );

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogTitle>
                    {project ? 'Project settings' : 'New project'}
                </DialogTitle>
                <DialogDescription>
                    {project
                        ? 'Change how this project is named, run and shared.'
                        : 'Group tickets for a customer job or your own work.'}
                </DialogDescription>

                <Form
                    {...(project
                        ? UpdateProjectController.form(project.id)
                        : StoreProjectController.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="flex items-start gap-3">
                                <ProjectIcon
                                    icon={icon}
                                    hue={hue ?? null}
                                    size="lg"
                                />
                                <div className="flex-1">
                                    <FormField
                                        id="project-name"
                                        label="Name"
                                        error={errors.name}
                                    >
                                        <Input
                                            id="project-name"
                                            name="name"
                                            defaultValue={project?.name}
                                            placeholder="Office refit"
                                            required
                                            autoFocus
                                        />
                                    </FormField>
                                </div>
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <Field label="Colour">
                                    <HuePicker
                                        name="hue"
                                        value={hue}
                                        onChange={setHue}
                                    />
                                </Field>
                                <Field label="Icon">
                                    <input
                                        type="hidden"
                                        name="icon"
                                        value={icon}
                                    />
                                    <div className="flex flex-wrap gap-1">
                                        {Object.entries(projectIcons).map(
                                            ([name, Icon]) => (
                                                <button
                                                    key={name}
                                                    type="button"
                                                    aria-label={name}
                                                    aria-pressed={icon === name}
                                                    onClick={() =>
                                                        setIcon(name)
                                                    }
                                                    className={cn(
                                                        'flex size-7 items-center justify-center rounded-md border text-muted-foreground',
                                                        icon === name &&
                                                            'border-primary bg-primary/8 text-foreground',
                                                    )}
                                                >
                                                    <Icon className="size-3.5" />
                                                </button>
                                            ),
                                        )}
                                    </div>
                                </Field>
                            </div>

                            <FormField
                                id="project-description"
                                label="Description"
                                error={errors.description}
                            >
                                <Textarea
                                    id="project-description"
                                    name="description"
                                    rows={3}
                                    defaultValue={project?.description ?? ''}
                                />
                            </FormField>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <Select
                                    id="project-status"
                                    name="status"
                                    label="Status"
                                    value={project?.status ?? 'planning'}
                                    choices={options.statuses}
                                    error={errors.status}
                                />
                                {canChangeVisibility && (
                                    <Select
                                        id="project-visibility"
                                        name="visibility"
                                        label="Who can see it"
                                        value={project?.visibility ?? 'open'}
                                        choices={options.visibilities}
                                        error={errors.visibility}
                                    />
                                )}
                                <Select
                                    id="project-customer"
                                    name="customer_id"
                                    label="Customer"
                                    value={project?.customer_id ?? ''}
                                    empty="Internal project"
                                    choices={options.customers.map(
                                        (customer) => ({
                                            value: customer.id,
                                            label: customer.name,
                                        }),
                                    )}
                                    error={errors.customer_id}
                                />
                                <Select
                                    id="project-lead"
                                    name="lead_id"
                                    label="Lead"
                                    value={project?.lead_id ?? ''}
                                    empty="Me"
                                    choices={options.members.map((member) => ({
                                        value: member.id,
                                        label: member.name,
                                    }))}
                                    error={errors.lead_id}
                                />
                                {options.groups.length > 0 && (
                                    <Select
                                        id="project-group"
                                        name="group_id"
                                        label="Group"
                                        value={project?.group_id ?? ''}
                                        empty="No group"
                                        choices={options.groups.map(
                                            (group) => ({
                                                value: group.id,
                                                label: group.name,
                                            }),
                                        )}
                                        error={errors.group_id}
                                    />
                                )}
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <FormField
                                    id="project-starts-on"
                                    label="Start date"
                                    error={errors.starts_on}
                                >
                                    <Input
                                        id="project-starts-on"
                                        name="starts_on"
                                        type="date"
                                        defaultValue={project?.starts_on ?? ''}
                                    />
                                </FormField>
                                <FormField
                                    id="project-due-on"
                                    label="Due date"
                                    error={errors.due_on}
                                >
                                    <Input
                                        id="project-due-on"
                                        name="due_on"
                                        type="date"
                                        defaultValue={project?.due_on ?? ''}
                                    />
                                </FormField>
                            </div>

                            {canManageMembers && options.members.length > 1 && (
                                <Field label="Members">
                                    <div className="flex flex-wrap gap-1.5">
                                        {options.members.map((member) => {
                                            const isMember = memberIds.includes(
                                                member.id,
                                            );

                                            return (
                                                <button
                                                    key={member.id}
                                                    type="button"
                                                    aria-pressed={isMember}
                                                    onClick={() =>
                                                        setMemberIds(
                                                            isMember
                                                                ? memberIds.filter(
                                                                      (id) =>
                                                                          id !==
                                                                          member.id,
                                                                  )
                                                                : [
                                                                      ...memberIds,
                                                                      member.id,
                                                                  ],
                                                        )
                                                    }
                                                    className={cn(
                                                        'rounded-full border px-2.5 py-0.5 text-xs',
                                                        isMember
                                                            ? 'border-primary/30 bg-primary/8 text-foreground'
                                                            : 'text-muted-foreground',
                                                    )}
                                                >
                                                    {member.name}
                                                </button>
                                            );
                                        })}
                                    </div>
                                    <input
                                        type="hidden"
                                        name="members_sent"
                                        value="1"
                                    />
                                    {memberIds.map((id) => (
                                        <input
                                            key={id}
                                            type="hidden"
                                            name="member_ids[]"
                                            value={id}
                                        />
                                    ))}
                                </Field>
                            )}

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => onOpenChange(false)}
                                >
                                    Cancel
                                </Button>
                                <Button disabled={processing}>
                                    {processing && <Spinner />}
                                    {project ? 'Save' : 'Create project'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="grid gap-2">
            <span className="text-sm font-medium">{label}</span>
            {children}
        </div>
    );
}

function Select({
    id,
    name,
    label,
    value,
    choices,
    empty,
    error,
}: {
    id: string;
    name: string;
    label: string;
    value: string;
    choices: { value: string; label: string }[];
    empty?: string;
    error?: string;
}) {
    return (
        <FormField id={id} label={label} error={error}>
            <NativeSelect id={id} name={name} defaultValue={value}>
                {empty !== undefined && <option value="">{empty}</option>}
                {choices.map((choice) => (
                    <option key={choice.value} value={choice.value}>
                        {choice.label}
                    </option>
                ))}
            </NativeSelect>
        </FormField>
    );
}
