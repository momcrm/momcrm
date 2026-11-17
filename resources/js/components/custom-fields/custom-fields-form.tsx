import { useState } from 'react';
import {
    CustomFieldInput,
    useCustomFields,
} from '@/components/custom-fields/custom-field-input';
import FormField from '@/components/form-field';
import { LabelChips } from '@/components/labels';
import { ChoiceTag, formatDay } from '@/components/tickets/ticket-meta';
import type {
    CustomField,
    CustomFieldTarget,
    CustomFieldValue,
    CustomFieldValues,
} from '@/types';

/**
 * The validation error of one custom field, whichever part of its value it
 * is about.
 */
export function customFieldError(
    errors: Record<string, string>,
    fieldId: string,
): string | undefined {
    const key = `custom_fields.${fieldId}`;

    return (
        errors[key] ??
        Object.entries(errors).find(([name]) => name.startsWith(`${key}.`))?.[1]
    );
}

/**
 * One labelled input per custom field of a kind of record, for a form.
 *
 * Without `onChange` it keeps its own values and sends them with the
 * surrounding <Form> as hidden `custom_fields[<id>]` fields; an emptied field
 * is sent blank, which clears it. With `onChange` the caller holds the values.
 * It renders nothing when the team has no fields for the record.
 */
export default function CustomFieldsFormFields({
    target,
    values,
    onChange,
    errors,
    disabled = false,
}: {
    target: CustomFieldTarget;
    values?: CustomFieldValues;
    onChange?: (values: CustomFieldValues) => void;
    errors: Record<string, string>;
    disabled?: boolean;
}) {
    const fields = useCustomFields(target);
    const [own, setOwn] = useState<CustomFieldValues>(values ?? {});
    const current = onChange ? (values ?? {}) : own;
    const change = (fieldId: string, value: CustomFieldValue) => {
        const next = { ...current, [fieldId]: value };

        if (onChange) {
            onChange(next);
        } else {
            setOwn(next);
        }
    };

    return (
        <>
            {fields.map((field) => (
                <FormField
                    key={field.id}
                    id={`custom-field-${field.id}`}
                    label={field.name}
                    hint={field.description ?? undefined}
                    error={customFieldError(errors, field.id)}
                >
                    <fieldset disabled={disabled} className="min-w-0">
                        <CustomFieldInput
                            id={`custom-field-${field.id}`}
                            field={field}
                            value={current[field.id]}
                            onChange={(value) => change(field.id, value)}
                        />
                    </fieldset>
                    {!onChange && (
                        <HiddenValue
                            fieldId={field.id}
                            value={current[field.id]}
                        />
                    )}
                </FormField>
            ))}
        </>
    );
}

function HiddenValue({
    fieldId,
    value,
}: {
    fieldId: string;
    value: CustomFieldValue | undefined;
}) {
    const name = `custom_fields[${fieldId}]`;

    if (Array.isArray(value) && value.length > 0) {
        return (
            <>
                {value.map((item) => (
                    <input
                        key={item}
                        type="hidden"
                        name={`${name}[]`}
                        value={item}
                    />
                ))}
            </>
        );
    }

    return (
        <input
            type="hidden"
            name={name}
            value={
                value === true
                    ? '1'
                    : typeof value === 'string' || typeof value === 'number'
                      ? value
                      : ''
            }
        />
    );
}

/**
 * Whether a record holds something to show for a field. An option the field
 * no longer offers counts as nothing.
 */
export function hasCustomFieldValue(
    field: CustomField,
    value: CustomFieldValue | undefined,
): boolean {
    if (value === undefined || value === null || value === '') {
        return false;
    }

    if (field.type === 'select') {
        return field.options.some((option) => option.id === value);
    }

    if (field.type === 'multi_select') {
        return (
            Array.isArray(value) &&
            field.options.some((option) => value.includes(option.id))
        );
    }

    return true;
}

/**
 * A custom field's value as read-only text or tags.
 */
export function CustomFieldValueView({
    field,
    value,
}: {
    field: CustomField;
    value: CustomFieldValue | undefined;
}) {
    if (!hasCustomFieldValue(field, value)) {
        return <span className="text-muted-foreground">—</span>;
    }

    switch (field.type) {
        case 'date':
            return <>{formatDay(String(value))}</>;

        case 'checkbox':
            return <>Yes</>;

        case 'select': {
            const option = field.options.find((item) => item.id === value);

            return option ? <ChoiceTag choice={option} /> : null;
        }

        case 'multi_select':
            return (
                <LabelChips
                    labels={field.options.filter(
                        (option) =>
                            Array.isArray(value) && value.includes(option.id),
                    )}
                />
            );

        default:
            return <>{String(value)}</>;
    }
}
