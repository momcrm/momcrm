import { Form } from '@inertiajs/react';
import CustomFieldsFormFields from '@/components/custom-fields/custom-fields-form';
import LinkedRecords from '@/components/custom-fields/linked-records';
import FormField from '@/components/form-field';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { update } from '@/routes/milestones';
import type { Milestone } from '@/types';

/**
 * A milestone's details: its name, due date and custom fields, and what
 * links to it. Whether it is done is ticked in the list, not here.
 */
export default function MilestoneDialog({
    milestone,
    canEdit,
    onClose,
}: {
    milestone: Milestone;
    canEdit: boolean;
    onClose: () => void;
}) {
    return (
        <Dialog open onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className="max-h-[88vh] overflow-y-auto">
                <DialogTitle>{milestone.name}</DialogTitle>
                <DialogDescription>
                    {canEdit
                        ? 'Change the milestone or fill in its fields.'
                        : 'You can view this milestone but not change it.'}
                </DialogDescription>
                <Form
                    {...update.form(milestone.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <fieldset
                                disabled={!canEdit}
                                className="grid gap-5 sm:grid-cols-2"
                            >
                                <FormField
                                    id="milestone-name"
                                    label="Name"
                                    error={errors.name}
                                >
                                    <Input
                                        id="milestone-name"
                                        name="name"
                                        defaultValue={milestone.name}
                                        maxLength={255}
                                        required
                                    />
                                </FormField>
                                <FormField
                                    id="milestone-due-on"
                                    label="Due"
                                    error={errors.due_on}
                                >
                                    <Input
                                        id="milestone-due-on"
                                        name="due_on"
                                        type="date"
                                        defaultValue={milestone.due_on}
                                        required
                                    />
                                </FormField>
                                <CustomFieldsFormFields
                                    target="milestone"
                                    values={milestone.custom_fields}
                                    errors={errors}
                                    disabled={!canEdit}
                                />
                            </fieldset>

                            <LinkedRecords groups={milestone.linked_records} />

                            <DialogFooter className="gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={onClose}
                                >
                                    {canEdit ? 'Cancel' : 'Close'}
                                </Button>
                                {canEdit && (
                                    <Button disabled={processing}>Save</Button>
                                )}
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
