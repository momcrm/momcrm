import { Form } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { useState } from 'react';
import FormField from '@/components/form-field';
import InputError from '@/components/input-error';
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
import { cn } from '@/lib/utils';
import { store, update } from '@/routes/access-rules';
import type {
    AccessCondition,
    AccessRuleItem,
    AccessRuleOptions,
} from '@/types';

type Attribute = AccessRuleOptions['attributes'][number];

/**
 * Pill buttons for picking several values; none picked shows the hint.
 */
function Chips({
    choices,
    selected,
    onChange,
}: {
    choices: { value: string; label: string }[];
    selected: string[];
    onChange: (selected: string[]) => void;
}) {
    return (
        <div className="flex flex-wrap gap-1.5">
            {choices.map((choice) => {
                const isOn = selected.includes(choice.value);

                return (
                    <button
                        key={choice.value}
                        type="button"
                        aria-pressed={isOn}
                        onClick={() =>
                            onChange(
                                isOn
                                    ? selected.filter(
                                          (value) => value !== choice.value,
                                      )
                                    : [...selected, choice.value],
                            )
                        }
                        className={cn(
                            'rounded-full border px-2.5 py-0.5 text-xs',
                            isOn
                                ? 'border-primary/30 bg-primary/8 text-foreground'
                                : 'text-muted-foreground',
                        )}
                    >
                        {choice.label}
                    </button>
                );
            })}
        </div>
    );
}

/**
 * Attributes a rule may use: the person's always, a record's only when the
 * rule covers that one kind of record.
 */
function usableAttributes(
    attributes: Attribute[],
    resources: string[],
): Attribute[] {
    return attributes.filter(
        (attribute) =>
            attribute.resource === null ||
            (resources.length === 1 && resources[0] === attribute.resource),
    );
}

function blankCondition(attribute: Attribute): AccessCondition {
    const operator = attribute.operators[0];

    return {
        attribute: attribute.value,
        operator,
        value:
            attribute.type === 'boolean'
                ? null
                : operator === 'in' || operator === 'not_in'
                  ? []
                  : (attribute.options[0]?.value ?? ''),
    };
}

/**
 * Adds or edits an access rule: what it covers (records and actions), whether
 * it allows or denies, and the conditions that must all hold.
 */
export default function AccessRuleFormDialog({
    open,
    onOpenChange,
    options,
    rule,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    options: AccessRuleOptions;
    rule?: AccessRuleItem;
}) {
    const [name, setName] = useState(rule?.name ?? '');
    const [effect, setEffect] = useState<string>(rule?.effect ?? 'allow');
    const [resources, setResources] = useState<string[]>(rule?.resources ?? []);
    const [actions, setActions] = useState<string[]>(rule?.actions ?? []);
    const [conditions, setConditions] = useState<AccessCondition[]>(
        rule?.conditions ?? [],
    );

    const attributes = usableAttributes(options.attributes, resources);
    const attributeFor = (value: string) =>
        options.attributes.find((attribute) => attribute.value === value);
    const availableActions =
        resources.length === 0
            ? options.actions
            : options.actions.filter((action) =>
                  options.resources.some(
                      (resource) =>
                          resources.includes(resource.value) &&
                          resource.actions.includes(action.value),
                  ),
              );
    const operatorLabel = (value: string) =>
        options.operators.find((operator) => operator.value === value)?.label ??
        value;

    const changeResources = (next: string[]) => {
        const usable = usableAttributes(options.attributes, next).map(
            (attribute) => attribute.value,
        );
        setResources(next);
        setConditions(
            conditions.filter((condition) =>
                usable.includes(condition.attribute),
            ),
        );
    };

    const changeCondition = (index: number, next: AccessCondition) =>
        setConditions(
            conditions.map((condition, position) =>
                position === index ? next : condition,
            ),
        );

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogTitle>{rule ? 'Edit rule' : 'New rule'}</DialogTitle>
                <DialogDescription>
                    A rule applies when all its conditions hold. Deny rules win
                    over allow rules, and anything no rule allows is refused.
                </DialogDescription>
                <Form
                    {...(rule ? update.form(rule.id) : store.form())}
                    transform={() => ({
                        name,
                        effect,
                        is_enabled: rule?.is_enabled ?? true,
                        resources,
                        actions: actions.filter((action) =>
                            availableActions.some(
                                (available) => available.value === action,
                            ),
                        ),
                        conditions,
                    })}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <FormField
                                id="rule-name"
                                label="Name"
                                error={errors.name}
                            >
                                <Input
                                    id="rule-name"
                                    value={name}
                                    onChange={(event) =>
                                        setName(event.target.value)
                                    }
                                    placeholder="Leads can archive their project's tickets"
                                    required
                                />
                            </FormField>

                            <FormField
                                id="rule-effect"
                                label="Effect"
                                error={errors.effect}
                            >
                                <NativeSelect
                                    id="rule-effect"
                                    value={effect}
                                    onChange={(event) =>
                                        setEffect(event.target.value)
                                    }
                                >
                                    {options.effects.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>

                            <div className="grid gap-2">
                                <span className="text-sm font-medium">
                                    Records
                                </span>
                                <Chips
                                    choices={options.resources}
                                    selected={resources}
                                    onChange={changeResources}
                                />
                                <p className="text-xs text-muted-foreground">
                                    {resources.length === 0
                                        ? 'None picked: the rule covers every kind of record.'
                                        : 'Conditions on a record are available when one kind is picked.'}
                                </p>
                                <InputError message={errors.resources} />
                            </div>

                            <div className="grid gap-2">
                                <span className="text-sm font-medium">
                                    Actions
                                </span>
                                <Chips
                                    choices={availableActions}
                                    selected={actions}
                                    onChange={setActions}
                                />
                                <p className="text-xs text-muted-foreground">
                                    {actions.length === 0
                                        ? 'None picked: the rule covers every action.'
                                        : null}
                                </p>
                                <InputError message={errors.actions} />
                            </div>

                            <div className="grid gap-2">
                                <span className="text-sm font-medium">
                                    Conditions
                                </span>
                                {conditions.length === 0 && (
                                    <p className="text-xs text-muted-foreground">
                                        No conditions: the rule applies to
                                        everyone.
                                    </p>
                                )}
                                {conditions.map((condition, index) => {
                                    const attribute = attributeFor(
                                        condition.attribute,
                                    );
                                    const takesList =
                                        condition.operator === 'in' ||
                                        condition.operator === 'not_in';

                                    return (
                                        <div
                                            key={index}
                                            className="space-y-2 rounded-md border p-2.5"
                                        >
                                            <div className="flex flex-wrap items-center gap-2">
                                                <NativeSelect
                                                    aria-label="Attribute"
                                                    className="min-w-0 flex-1"
                                                    value={condition.attribute}
                                                    onChange={(event) => {
                                                        const next =
                                                            attributeFor(
                                                                event.target
                                                                    .value,
                                                            );

                                                        if (next) {
                                                            changeCondition(
                                                                index,
                                                                blankCondition(
                                                                    next,
                                                                ),
                                                            );
                                                        }
                                                    }}
                                                >
                                                    {attributes.map(
                                                        (option) => (
                                                            <option
                                                                key={
                                                                    option.value
                                                                }
                                                                value={
                                                                    option.value
                                                                }
                                                            >
                                                                {option.label}
                                                            </option>
                                                        ),
                                                    )}
                                                </NativeSelect>
                                                <NativeSelect
                                                    aria-label="Comparison"
                                                    className="w-36"
                                                    value={condition.operator}
                                                    onChange={(event) => {
                                                        const operator =
                                                            event.target.value;
                                                        const list =
                                                            operator === 'in' ||
                                                            operator ===
                                                                'not_in';
                                                        const current = [
                                                            condition.value,
                                                        ]
                                                            .flat()
                                                            .filter(
                                                                (
                                                                    value,
                                                                ): value is string =>
                                                                    typeof value ===
                                                                        'string' &&
                                                                    value !==
                                                                        '',
                                                            );
                                                        changeCondition(index, {
                                                            ...condition,
                                                            operator,
                                                            value:
                                                                attribute?.type ===
                                                                'boolean'
                                                                    ? null
                                                                    : list
                                                                      ? current
                                                                      : (current[0] ??
                                                                        attribute
                                                                            ?.options[0]
                                                                            ?.value ??
                                                                        ''),
                                                        });
                                                    }}
                                                >
                                                    {(
                                                        attribute?.operators ??
                                                        []
                                                    ).map((operator) => (
                                                        <option
                                                            key={operator}
                                                            value={operator}
                                                        >
                                                            {operatorLabel(
                                                                operator,
                                                            )}
                                                        </option>
                                                    ))}
                                                </NativeSelect>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    aria-label="Remove condition"
                                                    onClick={() =>
                                                        setConditions(
                                                            conditions.filter(
                                                                (_, position) =>
                                                                    position !==
                                                                    index,
                                                            ),
                                                        )
                                                    }
                                                >
                                                    <X />
                                                </Button>
                                            </div>
                                            {attribute?.type === 'choice' &&
                                                (takesList ? (
                                                    <Chips
                                                        choices={
                                                            attribute.options
                                                        }
                                                        selected={
                                                            Array.isArray(
                                                                condition.value,
                                                            )
                                                                ? condition.value
                                                                : []
                                                        }
                                                        onChange={(value) =>
                                                            changeCondition(
                                                                index,
                                                                {
                                                                    ...condition,
                                                                    value,
                                                                },
                                                            )
                                                        }
                                                    />
                                                ) : (
                                                    <NativeSelect
                                                        aria-label="Value"
                                                        value={
                                                            typeof condition.value ===
                                                            'string'
                                                                ? condition.value
                                                                : ''
                                                        }
                                                        onChange={(event) =>
                                                            changeCondition(
                                                                index,
                                                                {
                                                                    ...condition,
                                                                    value: event
                                                                        .target
                                                                        .value,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        {attribute.options.map(
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
                                                ))}
                                            <InputError
                                                message={
                                                    errors[
                                                        `conditions.${index}.attribute`
                                                    ] ??
                                                    errors[
                                                        `conditions.${index}.operator`
                                                    ] ??
                                                    errors[
                                                        `conditions.${index}.value`
                                                    ]
                                                }
                                            />
                                        </div>
                                    );
                                })}
                                {attributes.length > 0 && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="justify-self-start"
                                        onClick={() =>
                                            setConditions([
                                                ...conditions,
                                                blankCondition(attributes[0]),
                                            ])
                                        }
                                    >
                                        <Plus />
                                        Add condition
                                    </Button>
                                )}
                                <InputError message={errors.conditions} />
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
                                    {rule ? 'Save rule' : 'Add rule'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
