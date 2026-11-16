import { Fragment } from 'react';
import type { ReactNode } from 'react';
import { ChoiceTag } from '@/components/tickets/ticket-meta';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { hueStyle } from '@/lib/colors';
import { cn } from '@/lib/utils';
import type { Choice, LabelTag } from '@/types';

/**
 * The in-place editors of a record's property rows: each looks like plain
 * text until hovered, and saves as soon as it changes.
 */

const NONE = '__none__';

export const triggerClass =
    'flex h-8 w-full min-w-0 items-center gap-2 rounded-md px-2 text-left hover:bg-accent/60 data-[state=open]:bg-accent/60';

export function Picker({
    value,
    choices,
    onChange,
    children,
}: {
    value: string;
    /** A choice's section, when given, heads it and the choices after it. */
    choices: {
        value: string;
        label: string;
        hue?: number | null;
        section?: string;
    }[];
    onChange: (value: string) => void;
    children: ReactNode;
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger className={triggerClass}>
                {children}
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="start"
                className="max-h-72 w-56 overflow-y-auto"
            >
                <DropdownMenuRadioGroup
                    value={value}
                    onValueChange={(next) => next !== value && onChange(next)}
                >
                    {choices.map((choice, index) => (
                        <Fragment key={choice.value}>
                            {choice.section &&
                                choice.section !==
                                    choices[index - 1]?.section && (
                                    <DropdownMenuLabel className="text-xs font-normal text-muted-foreground">
                                        {choice.section}
                                    </DropdownMenuLabel>
                                )}
                            <DropdownMenuRadioItem value={choice.value}>
                                {choice.hue !== undefined && (
                                    <span
                                        aria-hidden
                                        className="size-2 rounded-[3px] hue-dot"
                                        style={hueStyle(choice.hue)}
                                    />
                                )}
                                {choice.label}
                            </DropdownMenuRadioItem>
                        </Fragment>
                    ))}
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

/**
 * A pick from one of the team's optional lists, such as the ticket's type or
 * group, which can also be cleared.
 */
export function OptionalChoicePicker({
    value,
    choices,
    none,
    onChange,
}: {
    value: string | null;
    choices: Pick<Choice, 'id' | 'name' | 'hue'>[];
    none: string;
    onChange: (value: string | null) => void;
}) {
    const current = choices.find((choice) => choice.id === value);

    return (
        <Picker
            value={value ?? NONE}
            choices={[
                { value: NONE, label: none },
                ...choices.map((choice) => ({
                    value: choice.id,
                    label: choice.name,
                    hue: choice.hue,
                })),
            ]}
            onChange={(id) => onChange(id === NONE ? null : id)}
        >
            {current ? (
                <ChoiceTag choice={current} />
            ) : (
                <span className="text-muted-foreground">{none}</span>
            )}
        </Picker>
    );
}

export function LabelPicker({
    selected,
    options,
    onChange,
    children,
}: {
    selected: string[];
    options: LabelTag[];
    onChange: (ids: string[]) => void;
    children: ReactNode;
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className={cn(triggerClass, 'h-auto min-h-8 py-1')}
            >
                {children}
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-56">
                {options.length === 0 && (
                    <p className="px-2 py-1.5 text-sm text-muted-foreground">
                        No labels yet.
                    </p>
                )}
                {options.map((label) => (
                    <DropdownMenuCheckboxItem
                        key={label.id}
                        checked={selected.includes(label.id)}
                        onSelect={(event) => event.preventDefault()}
                        onCheckedChange={(checked) =>
                            onChange(
                                checked
                                    ? [...selected, label.id]
                                    : selected.filter((id) => id !== label.id),
                            )
                        }
                    >
                        <span
                            aria-hidden
                            className={cn('hue-dot', 'size-2 rounded-full')}
                            style={hueStyle(label.hue)}
                        />
                        {label.name}
                    </DropdownMenuCheckboxItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

export function DateField({
    value,
    label,
    onChange,
}: {
    value: string | null;
    label: string;
    onChange: (value: string | null) => void;
}) {
    return (
        <input
            type="date"
            aria-label={label}
            defaultValue={value ?? ''}
            className={cn(triggerClass, !value && 'text-muted-foreground')}
            onChange={(event) => onChange(event.target.value || null)}
        />
    );
}
