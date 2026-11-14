import { Form } from '@inertiajs/react';
import { useState } from 'react';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
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
import StoreGroupController from '@/actions/App/Group/Controllers/StoreGroupController';
import UpdateGroupController from '@/actions/App/Group/Controllers/UpdateGroupController';
import { colorDot } from '@/lib/colors';
import { cn } from '@/lib/utils';
import type { ColorName, CustomerOption } from '@/types';

export type GroupFormValues = {
    id: string;
    name: string;
    description: string | null;
    color: ColorName;
    lead_id: string | null;
    member_ids: string[];
};

/**
 * Creating a group, or editing one when `group` is given: its name, colour,
 * description, lead and members.
 */
export default function GroupFormDialog({
    open,
    onOpenChange,
    members,
    colors,
    group,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    members: CustomerOption[];
    colors: ColorName[];
    group?: GroupFormValues;
}) {
    const [color, setColor] = useState<ColorName>(group?.color ?? 'teal');
    const [memberIds, setMemberIds] = useState<string[]>(
        group?.member_ids ?? [],
    );
    const [leadId, setLeadId] = useState(group?.lead_id ?? '');

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogTitle>{group ? 'Edit group' : 'New group'}</DialogTitle>
                <DialogDescription>
                    A group is a set of people in the team, such as Support or
                    Field techs. Projects can belong to one.
                </DialogDescription>
                <Form
                    {...(group
                        ? UpdateGroupController.form(group.id)
                        : StoreGroupController.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                id="group-name"
                                label="Name"
                                error={errors.name}
                            >
                                <Input
                                    id="group-name"
                                    name="name"
                                    defaultValue={group?.name}
                                    required
                                    autoFocus
                                />
                            </FormField>
                            <div className="grid gap-2">
                                <span className="text-sm font-medium">
                                    Colour
                                </span>
                                <input
                                    type="hidden"
                                    name="color"
                                    value={color}
                                />
                                <div className="flex flex-wrap gap-1.5">
                                    {colors.map((option) => (
                                        <button
                                            key={option}
                                            type="button"
                                            aria-label={option}
                                            aria-pressed={color === option}
                                            onClick={() => setColor(option)}
                                            className={cn(
                                                'size-6 rounded-md ring-offset-2 ring-offset-background',
                                                colorDot[option],
                                                color === option &&
                                                    'ring-2 ring-ring',
                                            )}
                                        />
                                    ))}
                                </div>
                            </div>
                            <FormField
                                id="group-description"
                                label="Description"
                                error={errors.description}
                            >
                                <Textarea
                                    id="group-description"
                                    name="description"
                                    rows={2}
                                    defaultValue={group?.description ?? ''}
                                />
                            </FormField>
                            <FormField
                                id="group-lead"
                                label="Lead"
                                error={errors.lead_id}
                            >
                                <NativeSelect
                                    id="group-lead"
                                    name="lead_id"
                                    value={leadId}
                                    onChange={(event) =>
                                        setLeadId(event.target.value)
                                    }
                                >
                                    <option value="">No lead</option>
                                    {members.map((member) => (
                                        <option
                                            key={member.id}
                                            value={member.id}
                                        >
                                            {member.name}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            <div className="grid gap-2">
                                <span className="text-sm font-medium">
                                    Members
                                </span>
                                <div className="flex flex-wrap gap-1.5">
                                    {members.map((member) => {
                                        const isMember =
                                            memberIds.includes(member.id) ||
                                            member.id === leadId;

                                        return (
                                            <button
                                                key={member.id}
                                                type="button"
                                                aria-pressed={isMember}
                                                disabled={member.id === leadId}
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
                                {[
                                    ...new Set([
                                        ...memberIds,
                                        ...(leadId ? [leadId] : []),
                                    ]),
                                ].map((id) => (
                                    <input
                                        key={id}
                                        type="hidden"
                                        name="member_ids[]"
                                        value={id}
                                    />
                                ))}
                            </div>
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
                                    {group ? 'Save' : 'Create group'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
