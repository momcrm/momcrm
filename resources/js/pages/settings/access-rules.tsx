import { Head, router } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import { useState } from 'react';
import AccessRuleFormDialog from '@/components/access-rules/access-rule-form-dialog';
import ConfirmDelete from '@/components/confirm-delete';
import Heading from '@/components/heading';
import SoftBadge from '@/components/soft-badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { destroy, update } from '@/routes/access-rules';
import type {
    AccessCondition,
    AccessRuleItem,
    AccessRuleOptions,
} from '@/types';

function labelFor(choices: { value: string; label: string }[], value: string) {
    return choices.find((choice) => choice.value === value)?.label ?? value;
}

/**
 * A rule in a sentence: "Archive on Tickets, when they lead the ticket's
 * project is yes".
 */
function describe(rule: AccessRuleItem, options: AccessRuleOptions): string {
    const actions =
        rule.actions.length === 0
            ? 'Any action'
            : rule.actions
                  .map((action) => labelFor(options.actions, action))
                  .join(', ');
    const resources =
        rule.resources.length === 0
            ? 'all records'
            : rule.resources
                  .map((resource) => labelFor(options.resources, resource))
                  .join(', ');

    return `${actions} on ${resources.toLowerCase()}`;
}

function describeCondition(
    condition: AccessCondition,
    options: AccessRuleOptions,
): string {
    const attribute = options.attributes.find(
        (option) => option.value === condition.attribute,
    );
    const values = [condition.value]
        .flat()
        .filter((value): value is string => typeof value === 'string')
        .map((value) => labelFor(attribute?.options ?? [], value))
        .join(', ');

    const label = attribute?.label ?? condition.attribute;
    const takesValue = !['is_true', 'is_false'].includes(condition.operator);

    return [
        label.charAt(0).toLowerCase() + label.slice(1),
        labelFor(options.operators, condition.operator),
        // A value can be missing once the record it named is deleted.
        values || (takesValue ? '(choose a value)' : ''),
    ]
        .filter(Boolean)
        .join(' ');
}

export default function AccessRules({
    rules,
    options,
}: {
    rules: AccessRuleItem[];
    options: AccessRuleOptions;
}) {
    const [editing, setEditing] = useState<AccessRuleItem | 'new' | null>(null);

    const toggle = (rule: AccessRuleItem) =>
        router.put(
            update.url(rule.id),
            { ...rule, is_enabled: !rule.is_enabled },
            { preserveScroll: true },
        );

    return (
        <>
            <Head title="Access rules" />

            <h1 className="sr-only">Access rules</h1>

            <div className="space-y-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Access rules"
                        description="Decide who may change what. The team owner can always act; otherwise a matching deny rule refuses, a matching allow rule permits, and anything no rule allows is refused."
                    />
                    <Button
                        size="sm"
                        className="shrink-0"
                        onClick={() => setEditing('new')}
                    >
                        <Plus />
                        Add rule
                    </Button>
                </div>

                {rules.length === 0 ? (
                    <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        No rules yet, so only the team owner can make changes.
                    </p>
                ) : (
                    <ul className="space-y-2">
                        {rules.map((rule) => (
                            <li
                                key={rule.id}
                                className={cn(
                                    'space-y-2 rounded-lg border bg-card p-3 shadow-xs',
                                    !rule.is_enabled && 'opacity-60',
                                )}
                            >
                                <div className="flex items-start gap-2">
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="font-medium">
                                                {rule.name}
                                            </span>
                                            <SoftBadge
                                                tone={
                                                    rule.effect === 'allow'
                                                        ? 'success'
                                                        : 'destructive'
                                                }
                                            >
                                                {labelFor(
                                                    options.effects,
                                                    rule.effect,
                                                )}
                                            </SoftBadge>
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {describe(rule, options)}
                                        </p>
                                        {rule.conditions.length > 0 && (
                                            <ul className="space-y-0.5 text-xs text-muted-foreground">
                                                {rule.conditions.map(
                                                    (condition, index) => (
                                                        <li key={index}>
                                                            {index === 0
                                                                ? 'When '
                                                                : 'And '}
                                                            {describeCondition(
                                                                condition,
                                                                options,
                                                            )}
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        )}
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        aria-label={`Edit ${rule.name}`}
                                        onClick={() => setEditing(rule)}
                                    >
                                        <Pencil />
                                    </Button>
                                </div>
                                <div className="flex items-center justify-between gap-2 border-t pt-2">
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id={`rule-enabled-${rule.id}`}
                                            checked={rule.is_enabled}
                                            onCheckedChange={() => toggle(rule)}
                                        />
                                        <Label
                                            htmlFor={`rule-enabled-${rule.id}`}
                                            className="text-sm font-normal"
                                        >
                                            In effect
                                        </Label>
                                    </div>
                                    <ConfirmDelete
                                        action={destroy.form(rule.id)}
                                        title={`Delete ${rule.name}?`}
                                        description="Turning a rule off keeps it for later; deleting removes it."
                                    />
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <AccessRuleFormDialog
                key={
                    editing === null
                        ? 'closed'
                        : editing === 'new'
                          ? 'new'
                          : editing.id
                }
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
                options={options}
                rule={
                    editing === null || editing === 'new' ? undefined : editing
                }
            />
        </>
    );
}
