import { Form } from '@inertiajs/react';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { store } from '@/routes/connectors';
import type { Connector, ConnectorField, ConnectorType } from '@/types';

// A group of fields is laid out on six columns; a field says how many it takes.
const spans: Record<number, string> = {
    1: 'sm:col-span-1',
    2: 'sm:col-span-2',
    3: 'sm:col-span-3',
    4: 'sm:col-span-4',
    5: 'sm:col-span-5',
    6: 'sm:col-span-6',
};

/**
 * The fields in the order they come, gathered into their groups: the ones
 * with no group first, then each titled group where it first appears.
 */
function grouped(
    fields: ConnectorField[],
): { title: string | null; fields: ConnectorField[] }[] {
    const groups: { title: string | null; fields: ConnectorField[] }[] = [];

    for (const field of fields) {
        const group = groups.find((item) => item.title === field.group);

        if (group) {
            group.fields.push(field);
        } else {
            groups.push({ title: field.group, fields: [field] });
        }
    }

    return groups;
}

/**
 * Connects an account of a type that is connected by filling in a form, or
 * changes one that is already connected. The form is drawn from the fields
 * the type declares, so a new type needs no page of its own. Secrets are
 * never sent back to the page, so they are typed in each time.
 */
export default function ConnectorFormDialog({
    type,
    connector,
    open,
    onOpenChange,
}: {
    /** The type being connected; none while the dialog is closed. */
    type: ConnectorType | null;
    /** The connector being changed; none when a new one is being added. */
    connector: Connector | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    if (type === null) {
        return null;
    }

    const isAccount = (field: ConnectorField) =>
        field.name === type.account_field;

    const startsAs = (field: ConnectorField): string | number => {
        if (field.kind === 'password') {
            return '';
        }

        if (connector === null) {
            return field.default ?? '';
        }

        return isAccount(field)
            ? connector.account
            : (connector.settings?.[field.name] ?? '');
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogTitle>
                    {connector
                        ? `Change ${connector.account}`
                        : `Connect ${type.label}`}
                </DialogTitle>
                <DialogDescription>
                    {type.form_introduction ?? type.description}
                </DialogDescription>
                <Form
                    {...store.form(type.value)}
                    // A new form for each connector, so its values are not left over from the last one.
                    key={`${type.value}:${connector?.id ?? 'new'}`}
                    className="space-y-4"
                    onSuccess={() => onOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            {grouped(type.fields).map((group) => {
                                const fields = (
                                    <div className="grid gap-3 sm:grid-cols-6">
                                        {group.fields.map((field) => (
                                            <div
                                                key={field.name}
                                                className={cn(
                                                    spans[field.span] ??
                                                        spans[6],
                                                )}
                                            >
                                                <FormField
                                                    id={`connector-${field.name}`}
                                                    label={field.label}
                                                    hint={
                                                        connector &&
                                                        isAccount(field)
                                                            ? 'To use another one, add it as a new connector.'
                                                            : (field.hint ??
                                                              undefined)
                                                    }
                                                    error={errors[field.name]}
                                                >
                                                    {field.kind === 'select' ? (
                                                        <NativeSelect
                                                            id={`connector-${field.name}`}
                                                            name={field.name}
                                                            required={
                                                                field.required
                                                            }
                                                            defaultValue={startsAs(
                                                                field,
                                                            )}
                                                        >
                                                            {field.options.map(
                                                                (option) => (
                                                                    <option
                                                                        key={
                                                                            option.value
                                                                        }
                                                                        value={
                                                                            option.value
                                                                        }
                                                                    >
                                                                        {
                                                                            option.label
                                                                        }
                                                                    </option>
                                                                ),
                                                            )}
                                                        </NativeSelect>
                                                    ) : (
                                                        <Input
                                                            id={`connector-${field.name}`}
                                                            name={field.name}
                                                            type={field.kind}
                                                            required={
                                                                field.required
                                                            }
                                                            readOnly={
                                                                connector !==
                                                                    null &&
                                                                isAccount(field)
                                                            }
                                                            defaultValue={startsAs(
                                                                field,
                                                            )}
                                                            placeholder={
                                                                field.placeholder ??
                                                                undefined
                                                            }
                                                            autoComplete={
                                                                field.kind ===
                                                                'password'
                                                                    ? 'new-password'
                                                                    : 'off'
                                                            }
                                                        />
                                                    )}
                                                </FormField>
                                            </div>
                                        ))}
                                    </div>
                                );

                                return group.title === null ? (
                                    <div key="">{fields}</div>
                                ) : (
                                    <fieldset
                                        key={group.title}
                                        className="rounded-lg border p-3"
                                    >
                                        <legend className="px-1 text-sm font-medium">
                                            {group.title}
                                        </legend>
                                        {fields}
                                    </fieldset>
                                );
                            })}

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary" type="button">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Checking…'
                                        : connector
                                          ? 'Save'
                                          : 'Connect'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
