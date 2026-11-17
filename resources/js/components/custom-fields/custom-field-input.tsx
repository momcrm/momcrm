import { usePage } from '@inertiajs/react';
import RecordPicker from '@/components/custom-fields/record-picker';
import { LabelChips } from '@/components/labels';
import NativeSelect from '@/components/native-select';
import {
    DateField,
    LabelPicker,
    OptionalChoicePicker,
    triggerClass,
} from '@/components/tickets/property-editors';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import type {
    CustomField,
    CustomFieldTarget,
    CustomFieldValue,
    CustomFieldValues,
} from '@/types';

/**
 * The fields the team has added to one kind of record, in order.
 */
export function useCustomFields(target: CustomFieldTarget): CustomField[] {
    return usePage().props.customFields?.[target] ?? [];
}

/**
 * Whether a list's toolbar offers a filter on the field: types with a short,
 * fixed set of values to pick from.
 */
export function isFilterableCustomField(field: CustomField): boolean {
    return ['checkbox', 'select', 'multi_select'].includes(field.type);
}

/**
 * Whether a list can be ordered by the field, as the server allows.
 */
export function isSortableCustomField(field: CustomField): boolean {
    return ['text', 'number', 'date', 'checkbox', 'select'].includes(
        field.type,
    );
}

/**
 * What a filter on the field is set to, in words; null when it is not set.
 */
export function customFieldFilterLabel(
    field: CustomField,
    value: string | undefined,
): string | null {
    if (value === undefined) {
        return null;
    }

    if (field.type === 'checkbox') {
        return value === '1' ? 'Yes' : 'No';
    }

    return value === 'none'
        ? 'None'
        : (field.options.find((option) => option.id === value)?.name ?? null);
}

/**
 * A record's values for the given fields only. A record can still hold
 * values for archived fields, which a form must not send back.
 */
export function pickCustomFieldValues(
    fields: CustomField[],
    values: CustomFieldValues,
): CustomFieldValues {
    return Object.fromEntries(
        fields
            .filter((field) => values[field.id] !== undefined)
            .map((field) => [field.id, values[field.id]]),
    );
}

/**
 * The names of the records that the record links on this page point at, by
 * record id. A page sends them as `customFieldLabels`, or with its ticket
 * drawer or customer workspace.
 */
export function useCustomFieldLabels(): Record<string, string> {
    const props = usePage().props as {
        customFieldLabels?: Record<string, string>;
        workspace?: { custom_field_labels?: Record<string, string> };
        drawer?: { custom_field_labels?: Record<string, string> } | null;
    };

    return {
        ...props.customFieldLabels,
        ...props.workspace?.custom_field_labels,
        ...props.drawer?.custom_field_labels,
    };
}

type InputProps = {
    field: CustomField;
    /** Undefined when the record holds nothing for the field. */
    value: CustomFieldValue | undefined;
    /** Null clears the field. */
    onChange: (value: CustomFieldValue) => void;
};

const inlineInputClass =
    'h-control-md border-transparent bg-transparent px-2 shadow-none hover:border-border';

function selectedIds(field: CustomField, value: InputProps['value']): string[] {
    const ids = Array.isArray(value) ? value : [];

    return field.options
        .filter((option) => ids.includes(option.id))
        .map((option) => option.id);
}

/**
 * A custom field edited in place in a record's property rows: it saves as
 * soon as it changes, like the rows around it.
 */
export function InlineCustomFieldInput({ field, value, onChange }: InputProps) {
    const labels = useCustomFieldLabels();

    switch (field.type) {
        case 'relation':
            return (
                <RecordPicker
                    field={field}
                    value={typeof value === 'string' ? value : null}
                    label={
                        typeof value === 'string' ? labels[value] : undefined
                    }
                    onChange={onChange}
                    className={triggerClass}
                />
            );

        case 'text':
        case 'number': {
            const current = value === undefined || value === null ? '' : value;

            return (
                <Input
                    key={String(current)}
                    type={field.type === 'number' ? 'number' : 'text'}
                    step="any"
                    aria-label={field.name}
                    placeholder="—"
                    defaultValue={String(current)}
                    maxLength={field.type === 'text' ? 1000 : undefined}
                    className={inlineInputClass}
                    onBlur={(event) => {
                        const next = event.target.value.trim();

                        if (next !== String(current)) {
                            onChange(next === '' ? null : next);
                        }
                    }}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                            event.currentTarget.blur();
                        }
                    }}
                />
            );
        }

        case 'date':
            return (
                <DateField
                    key={typeof value === 'string' ? value : ''}
                    label={field.name}
                    value={typeof value === 'string' ? value : null}
                    onChange={onChange}
                />
            );

        case 'checkbox':
            return (
                <span className="flex h-8 items-center px-2">
                    <Checkbox
                        aria-label={field.name}
                        checked={value === true}
                        onCheckedChange={(checked) =>
                            onChange(checked === true ? true : null)
                        }
                    />
                </span>
            );

        case 'select':
            return (
                <OptionalChoicePicker
                    value={typeof value === 'string' ? value : null}
                    choices={field.options}
                    none="None"
                    onChange={onChange}
                />
            );

        case 'multi_select': {
            const selected = selectedIds(field, value);

            return (
                <LabelPicker
                    selected={selected}
                    options={field.options}
                    onChange={(ids) => onChange(ids.length > 0 ? ids : null)}
                >
                    {selected.length > 0 ? (
                        <LabelChips
                            labels={field.options.filter((option) =>
                                selected.includes(option.id),
                            )}
                        />
                    ) : (
                        <span className="text-muted-foreground">None</span>
                    )}
                </LabelPicker>
            );
        }
    }
}

/**
 * A custom field as a form control: it holds its value until the form
 * around it is submitted.
 */
export function CustomFieldInput({
    field,
    value,
    onChange,
    id,
}: InputProps & { id: string }) {
    const labels = useCustomFieldLabels();

    switch (field.type) {
        case 'relation':
            return (
                <RecordPicker
                    id={id}
                    field={field}
                    value={typeof value === 'string' ? value : null}
                    label={
                        typeof value === 'string' ? labels[value] : undefined
                    }
                    onChange={onChange}
                    className="flex h-control-md w-full min-w-0 items-center rounded-md border border-border-strong bg-card px-2.5 text-left text-sm"
                />
            );

        case 'text':
        case 'number':
        case 'date':
            return (
                <Input
                    id={id}
                    type={field.type}
                    step="any"
                    value={
                        typeof value === 'string' || typeof value === 'number'
                            ? value
                            : ''
                    }
                    maxLength={field.type === 'text' ? 1000 : undefined}
                    onChange={(event) => onChange(event.target.value || null)}
                />
            );

        case 'checkbox':
            return (
                <span className="flex h-control-md items-center">
                    <Checkbox
                        id={id}
                        checked={value === true}
                        onCheckedChange={(checked) =>
                            onChange(checked === true ? true : null)
                        }
                    />
                </span>
            );

        case 'select':
            return (
                <NativeSelect
                    id={id}
                    value={typeof value === 'string' ? value : ''}
                    onChange={(event) => onChange(event.target.value || null)}
                >
                    <option value="">None</option>
                    {field.options.map((option) => (
                        <option key={option.id} value={option.id}>
                            {option.name}
                        </option>
                    ))}
                </NativeSelect>
            );

        case 'multi_select': {
            const selected = selectedIds(field, value);

            return (
                <div className="rounded-md border border-border-strong bg-card">
                    <LabelPicker
                        selected={selected}
                        options={field.options}
                        onChange={(ids) =>
                            onChange(ids.length > 0 ? ids : null)
                        }
                    >
                        {selected.length > 0 ? (
                            <LabelChips
                                labels={field.options.filter((option) =>
                                    selected.includes(option.id),
                                )}
                            />
                        ) : (
                            <span className="text-muted-foreground">None</span>
                        )}
                    </LabelPicker>
                </div>
            );
        }
    }
}
