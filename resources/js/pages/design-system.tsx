import { Head } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowUpRight,
    Building2,
    Calendar,
    Check,
    CircleDashed,
    Clock,
    Moon,
    Plus,
    Sun,
    UserPlus,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import EmptyState from '@/components/empty-state';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import SoftBadge from '@/components/soft-badge';
import StatusPill from '@/components/status-pill';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { designSystem } from '@/routes';
import type { Tone } from '@/types';

type Swatch = {
    token: string;
    utility: string;
    note?: string;
};

const colorGroups: { title: string; swatches: Swatch[] }[] = [
    {
        title: 'Surfaces',
        swatches: [
            { token: '--background', utility: 'bg-background', note: 'Page' },
            { token: '--card', utility: 'bg-card', note: 'Cards, popovers' },
            { token: '--sidebar', utility: 'bg-sidebar', note: 'Sidebar' },
            { token: '--muted', utility: 'bg-muted', note: 'Sunken, wells' },
            { token: '--accent', utility: 'bg-accent', note: 'Hover' },
        ],
    },
    {
        title: 'Text',
        swatches: [
            {
                token: '--foreground',
                utility: 'text-foreground',
                note: 'Primary',
            },
            {
                token: '--sidebar-foreground',
                utility: 'text-sidebar-foreground',
                note: 'Secondary',
            },
            {
                token: '--muted-foreground',
                utility: 'text-muted-foreground',
                note: 'Supporting',
            },
            {
                token: '--subtle-foreground',
                utility: 'text-subtle-foreground',
                note: 'Placeholder, meta',
            },
        ],
    },
    {
        title: 'Lines',
        swatches: [
            { token: '--border', utility: 'border-border', note: 'Default' },
            {
                token: '--border-strong',
                utility: 'border-border-strong',
                note: 'Emphasis',
            },
            { token: '--input', utility: 'border-input', note: 'Fields' },
            { token: '--ring', utility: 'ring-ring', note: 'Focus' },
        ],
    },
    {
        title: 'Brand & status',
        swatches: [
            { token: '--primary', utility: 'bg-primary', note: 'Accent' },
            { token: '--success', utility: 'text-success', note: 'Done, paid' },
            {
                token: '--warning',
                utility: 'text-warning',
                note: 'Pending, due',
            },
            {
                token: '--destructive',
                utility: 'text-destructive',
                note: 'Overdue, errors',
            },
            { token: '--info', utility: 'text-info', note: 'Informational' },
            { token: '--violet', utility: 'text-violet', note: 'Review' },
            { token: '--teal', utility: 'text-teal', note: 'Tag colour' },
            { token: '--rose', utility: 'text-rose', note: 'Tag colour' },
            { token: '--orange', utility: 'text-orange', note: 'Tag colour' },
        ],
    },
    {
        title: 'Charts',
        swatches: [1, 2, 3, 4, 5].map((index) => ({
            token: `--chart-${index}`,
            utility: `bg-chart-${index}`,
        })),
    },
];

const typeScale: {
    utility: string;
    size: string;
    use: string;
    className: string;
}[] = [
    {
        utility: 'text-2xl',
        size: '24px',
        use: 'Page greeting',
        className: 'text-2xl font-semibold tracking-tight',
    },
    {
        utility: 'text-xl',
        size: '20px',
        use: 'Page title',
        className: 'text-xl font-semibold tracking-tight',
    },
    {
        utility: 'text-base',
        size: '16px',
        use: 'Section heading',
        className: 'text-base font-medium',
    },
    {
        utility: 'text-title',
        size: '14.5px',
        use: 'Dialog and card titles',
        className: 'text-title font-semibold',
    },
    {
        utility: 'text-sm',
        size: '13.5px',
        use: 'Body, fields, menus, tables',
        className: 'text-sm',
    },
    {
        utility: 'text-ui',
        size: '12.5px',
        use: 'Buttons, labels',
        className: 'text-ui font-medium',
    },
    {
        utility: 'text-xs',
        size: '12px',
        use: 'Hints, meta, badges, small buttons',
        className: 'text-xs text-muted-foreground',
    },
    {
        utility: 'text-2xs',
        size: '11px',
        use: 'Tooltips, counts',
        className: 'text-2xs font-medium text-muted-foreground',
    },
];

const controlHeights = [
    { utility: 'h-control-xs', size: '22px', use: 'Tiny icon buttons' },
    { utility: 'h-control-sm', size: '26px', use: 'Small buttons, toolbars' },
    {
        utility: 'h-control-md',
        size: '30px',
        use: 'Buttons, fields, menu rows',
    },
    { utility: 'h-control-lg', size: '36px', use: 'Large buttons' },
];

const radii = [
    { utility: 'rounded-sm', size: '4px' },
    { utility: 'rounded-md', size: '6px' },
    { utility: 'rounded-lg', size: '8px' },
    { utility: 'rounded-xl', size: '10px' },
    { utility: 'rounded-[12px]', size: '12px' },
    { utility: 'rounded-full', size: '999px' },
];

const shadows = [
    { utility: 'shadow-xs', use: 'Buttons, fields' },
    { utility: 'shadow-card', use: 'Cards on the page' },
    { utility: 'shadow-pop', use: 'Menus, popovers, dialogs' },
    { utility: 'shadow-drag', use: 'Cards being dragged' },
];

const spacing = [1, 2, 3, 4, 5, 6, 8, 10, 12];

const sections = [
    { id: 'colors', title: 'Colour' },
    { id: 'typography', title: 'Typography' },
    { id: 'shape', title: 'Shape & depth' },
    { id: 'buttons', title: 'Buttons' },
    { id: 'status', title: 'Badges & status' },
    { id: 'forms', title: 'Forms' },
    { id: 'patterns', title: 'Patterns' },
    { id: 'feedback', title: 'Feedback' },
];

/**
 * Reads each CSS custom property's current value, re-reading whenever the
 * light/dark appearance changes.
 */
function useTokenValues(tokens: string[]): Record<string, string> {
    const { resolvedAppearance } = useAppearance();
    const [values, setValues] = useState<Record<string, string>>({});

    useEffect(() => {
        const styles = getComputedStyle(document.documentElement);

        setValues(
            Object.fromEntries(
                tokens.map((token) => [
                    token,
                    styles.getPropertyValue(token).trim(),
                ]),
            ),
        );
    }, [resolvedAppearance]); // eslint-disable-line react-hooks/exhaustive-deps

    return values;
}

function Section({
    id,
    title,
    description,
    children,
}: {
    id: string;
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <section id={id} className="scroll-mt-20 space-y-4">
            <div className="space-y-0.5">
                <h2 className="text-base font-medium">{title}</h2>
                <p className="text-sm text-muted-foreground">{description}</p>
            </div>
            {children}
        </section>
    );
}

function Panel({
    title,
    action,
    className,
    children,
}: {
    title?: string;
    action?: ReactNode;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div className={cn('rounded-xl bg-card shadow-card', className)}>
            {title && (
                <div className="flex h-11 items-center justify-between gap-4 px-4">
                    <h3 className="text-sm font-medium">{title}</h3>
                    {action}
                </div>
            )}
            <div className={cn(title ? 'px-4 pb-4' : 'p-4')}>{children}</div>
        </div>
    );
}

function Token({ children }: { children: ReactNode }) {
    return (
        <code className="font-mono text-xs text-muted-foreground">
            {children}
        </code>
    );
}

function SwatchTile({ swatch, value }: { swatch: Swatch; value?: string }) {
    return (
        <div className="flex items-center gap-3 rounded-lg p-1.5">
            <span
                className="size-9 shrink-0 rounded-md shadow-[inset_0_0_0_1px_var(--border-strong)]"
                style={{ background: `var(${swatch.token})` }}
            />
            <div className="min-w-0">
                <div className="flex items-baseline gap-2">
                    <span className="truncate text-sm font-medium">
                        {swatch.utility}
                    </span>
                    {swatch.note && (
                        <span className="truncate text-xs text-subtle-foreground">
                            {swatch.note}
                        </span>
                    )}
                </div>
                <Token>
                    {swatch.token}
                    {value && ` · ${value}`}
                </Token>
            </div>
        </div>
    );
}

type Status = 'planning' | 'in-progress' | 'at-risk' | 'done' | 'backlog';

const statusStyles: Record<Status, { label: string; tone: Tone }> = {
    backlog: { label: 'Backlog', tone: 'neutral' },
    planning: { label: 'Planning', tone: 'neutral' },
    'in-progress': { label: 'In Progress', tone: 'info' },
    'at-risk': { label: 'At Risk', tone: 'destructive' },
    done: { label: 'Done', tone: 'success' },
};

function Initials({ name, className }: { name: string; className?: string }) {
    const initials = name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2);

    return (
        <Avatar className={cn('size-5 ring-2 ring-card', className)}>
            <AvatarFallback className="bg-accent text-[9px] font-medium">
                {initials}
            </AvatarFallback>
        </Avatar>
    );
}

function Progress({ value }: { value: number }) {
    return (
        <div className="flex items-center gap-2">
            <div className="h-1 w-24 overflow-hidden rounded-full bg-accent">
                <div
                    className="h-full rounded-full bg-primary"
                    style={{ width: `${value}%` }}
                />
            </div>
            <span className="w-8 text-right text-xs text-muted-foreground tabular-nums">
                {value}%
            </span>
        </div>
    );
}

const sampleCustomers: {
    name: string;
    status: Status;
    progress: number;
    due: string;
    team: string[];
}[] = [
    {
        name: 'Harbour Dental',
        status: 'in-progress',
        progress: 62,
        due: 'Nov 28',
        team: ['Sarah Chen', 'John Carter', 'Emma Wilson'],
    },
    {
        name: 'Northside Logistics',
        status: 'planning',
        progress: 14,
        due: 'Dec 3',
        team: ['Priya Patel', 'Marcus Lee'],
    },
    {
        name: 'Westfield Legal',
        status: 'at-risk',
        progress: 8,
        due: 'Nov 21',
        team: ['John Carter'],
    },
    {
        name: 'Coastal Medical',
        status: 'done',
        progress: 100,
        due: 'Nov 2',
        team: ['Emma Wilson', 'Sarah Chen'],
    },
];

export default function DesignSystem() {
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const tokenValues = useTokenValues(
        colorGroups.flatMap((group) =>
            group.swatches.map((swatch) => swatch.token),
        ),
    );
    const [segment, setSegment] = useState('upcoming');

    return (
        <>
            <Head title="Design system" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-10 px-4 py-8 md:px-6">
                <header className="space-y-5">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="space-y-1">
                            <h1 className="text-2xl font-semibold tracking-tight">
                                Design system
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                The tokens and components momcrm is built from.
                                Everything here renders the real components.
                            </p>
                        </div>
                        <ToggleGroup
                            type="single"
                            variant="outline"
                            size="sm"
                            value={resolvedAppearance}
                            onValueChange={(value) => {
                                if (value === 'light' || value === 'dark') {
                                    updateAppearance(value);
                                }
                            }}
                            aria-label="Preview theme"
                        >
                            <ToggleGroupItem value="light" aria-label="Light">
                                <Sun />
                            </ToggleGroupItem>
                            <ToggleGroupItem value="dark" aria-label="Dark">
                                <Moon />
                            </ToggleGroupItem>
                        </ToggleGroup>
                    </div>

                    <nav className="flex flex-wrap gap-1 border-b pb-3">
                        {sections.map((section) => (
                            <a
                                key={section.id}
                                href={`#${section.id}`}
                                className="rounded-md px-2.5 py-1 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            >
                                {section.title}
                            </a>
                        ))}
                    </nav>
                </header>

                <Section
                    id="colors"
                    title="Colour"
                    description="Warm stone neutrals with a single indigo accent. Status colours are reserved for meaning, never decoration."
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        {colorGroups.map((group) => (
                            <Panel
                                key={group.title}
                                title={group.title}
                                className={
                                    group.title === 'Charts'
                                        ? 'md:col-span-2'
                                        : undefined
                                }
                            >
                                <div
                                    className={cn(
                                        'grid gap-1',
                                        group.title === 'Charts' &&
                                            'sm:grid-cols-2 lg:grid-cols-3',
                                    )}
                                >
                                    {group.swatches.map((swatch) => (
                                        <SwatchTile
                                            key={swatch.token}
                                            swatch={swatch}
                                            value={tokenValues[swatch.token]}
                                        />
                                    ))}
                                </div>
                            </Panel>
                        ))}
                    </div>
                </Section>

                <Section
                    id="typography"
                    title="Typography"
                    description="Geist for interface text, Geist Mono for codes and numbers. Body copy sits at 13.5px for dense, calm screens."
                >
                    <Panel>
                        <div className="divide-y">
                            {typeScale.map((step) => (
                                <div
                                    key={step.utility}
                                    className="grid items-baseline gap-2 py-3 first:pt-0 last:pb-0 sm:grid-cols-[9rem_1fr]"
                                >
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-sm font-medium">
                                            {step.utility}
                                        </span>
                                        <Token>{step.size}</Token>
                                    </div>
                                    <div className="flex flex-wrap items-baseline justify-between gap-x-4">
                                        <span className={step.className}>
                                            Harbour Dental renewal
                                        </span>
                                        <span className="text-xs text-subtle-foreground">
                                            {step.use}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Panel>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Panel title="Weights">
                            <div className="space-y-1.5 text-sm">
                                <p className="font-normal">
                                    400 · Regular for body text
                                </p>
                                <p className="font-medium">
                                    500 · Medium for labels and headings
                                </p>
                                <p className="font-semibold">
                                    600 · Semibold for titles and figures
                                </p>
                            </div>
                        </Panel>
                        <Panel title="Mono & figures">
                            <div className="space-y-1.5 text-sm">
                                <p className="font-mono">INV-2026-0142</p>
                                <p className="font-mono text-muted-foreground">
                                    TCK-0398 · $4,280.00
                                </p>
                                <p className="text-2xl font-semibold tabular-nums">
                                    $12,940
                                </p>
                            </div>
                        </Panel>
                    </div>
                </Section>

                <Section
                    id="shape"
                    title="Shape & depth"
                    description="Compact 22/26/30/36px controls, small radii, a 4px spacing grid and hairline shadows. Depth comes from a 1px ring, not from blur."
                >
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Panel title="Control heights">
                            <div className="space-y-2">
                                {controlHeights.map((height) => (
                                    <div
                                        key={height.utility}
                                        className="flex items-center gap-3"
                                    >
                                        <span
                                            className={cn(
                                                'w-8 shrink-0 rounded-md border-2 border-primary/40 bg-primary/10',
                                                height.utility,
                                            )}
                                        />
                                        <span className="min-w-0">
                                            <span className="block text-sm font-medium">
                                                {height.utility}{' '}
                                                <Token>{height.size}</Token>
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {height.use}
                                            </span>
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </Panel>
                        <Panel title="Radius">
                            <div className="space-y-2">
                                {radii.map((radius) => (
                                    <div
                                        key={radius.utility}
                                        className="flex items-center gap-3"
                                    >
                                        <span
                                            className={cn(
                                                'size-8 border-2 border-primary/40 bg-primary/10',
                                                radius.utility,
                                            )}
                                        />
                                        <span className="text-sm font-medium">
                                            {radius.utility}
                                        </span>
                                        <Token>{radius.size}</Token>
                                    </div>
                                ))}
                            </div>
                        </Panel>
                        <Panel title="Spacing">
                            <div className="space-y-2">
                                {spacing.map((step) => (
                                    <div
                                        key={step}
                                        className="flex items-center gap-3"
                                    >
                                        <span className="w-6 text-sm font-medium tabular-nums">
                                            {step}
                                        </span>
                                        <span
                                            className="h-2 rounded-sm bg-primary/70"
                                            style={{ width: step * 4 }}
                                        />
                                        <Token>{step * 4}px</Token>
                                    </div>
                                ))}
                            </div>
                        </Panel>
                        <Panel title="Elevation">
                            <div className="space-y-4 rounded-lg bg-muted p-4">
                                {shadows.map((shadow) => (
                                    <div
                                        key={shadow.utility}
                                        className={cn(
                                            'rounded-lg bg-card px-3 py-2.5',
                                            shadow.utility,
                                        )}
                                    >
                                        <div className="text-sm font-medium">
                                            {shadow.utility}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {shadow.use}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Panel>
                    </div>
                </Section>

                <Section
                    id="buttons"
                    title="Buttons"
                    description="One primary action per view. Outline (or secondary, which looks the same) for actions next to it, ghost inside toolbars and rows. 30px by default, 26px small, 36px large."
                >
                    <Panel>
                        <div className="space-y-5">
                            <div className="flex flex-wrap items-center gap-2">
                                <Button>
                                    <Plus /> New ticket
                                </Button>
                                <Button variant="outline">
                                    <UserPlus /> Invite member
                                </Button>
                                <Button variant="secondary">Secondary</Button>
                                <Button variant="ghost">Ghost</Button>
                                <Button variant="link">Link</Button>
                                <Button variant="destructive">Delete</Button>
                            </div>
                            <div className="flex flex-wrap items-center gap-2">
                                <Button size="sm">Small</Button>
                                <Button>Default</Button>
                                <Button size="lg">Large</Button>
                                <Button size="icon" variant="outline">
                                    <Calendar />
                                </Button>
                                <Button size="icon-sm" variant="ghost">
                                    <Calendar />
                                </Button>
                                <Button size="icon-xs" variant="ghost">
                                    <Calendar />
                                </Button>
                                <Button disabled>
                                    <Spinner /> Saving
                                </Button>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Button size="icon" variant="ghost">
                                            <ArrowUpRight />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        Open in full
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                        </div>
                    </Panel>
                </Section>

                <Section
                    id="status"
                    title="Badges & status"
                    description="Status pills pair a coloured dot with a neutral outline. Soft badges tint the background at 10%."
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        <Panel title="Status pills">
                            <div className="flex flex-wrap gap-2">
                                {(Object.keys(statusStyles) as Status[]).map(
                                    (status) => (
                                        <StatusPill
                                            key={status}
                                            tone={statusStyles[status].tone}
                                        >
                                            {statusStyles[status].label}
                                        </StatusPill>
                                    ),
                                )}
                            </div>
                        </Panel>
                        <Panel title="Soft badges">
                            <div className="flex flex-wrap gap-2">
                                <SoftBadge tone="primary">Assigned</SoftBadge>
                                <SoftBadge tone="success">Paid</SoftBadge>
                                <SoftBadge tone="warning">Due soon</SoftBadge>
                                <SoftBadge tone="destructive">
                                    Overdue
                                </SoftBadge>
                                <SoftBadge tone="info">Sent</SoftBadge>
                                <SoftBadge tone="neutral">Draft</SoftBadge>
                            </div>
                        </Panel>
                        <Panel title="Solid badges" className="md:col-span-2">
                            <div className="flex flex-wrap gap-2">
                                <Badge>Default</Badge>
                                <Badge variant="secondary">Secondary</Badge>
                                <Badge variant="outline">Outline</Badge>
                                <Badge variant="destructive">Urgent</Badge>
                            </div>
                        </Panel>
                    </div>
                </Section>

                <Section
                    id="forms"
                    title="Forms"
                    description="Labels above fields, hints below, errors last. Fields share the strong border and the indigo focus ring."
                >
                    <Panel>
                        <div className="grid gap-5 md:grid-cols-2">
                            <FormField
                                id="ds-name"
                                label="Customer name"
                                hint="As it appears on invoices."
                            >
                                <Input
                                    id="ds-name"
                                    placeholder="Harbour Dental"
                                />
                            </FormField>
                            <FormField id="ds-status" label="Status">
                                <NativeSelect
                                    id="ds-status"
                                    defaultValue="open"
                                >
                                    <option value="open">Open</option>
                                    <option value="pending">Pending</option>
                                    <option value="closed">Closed</option>
                                </NativeSelect>
                            </FormField>
                            <FormField
                                id="ds-email"
                                label="Billing email"
                                error="Enter a valid email address."
                            >
                                <Input
                                    id="ds-email"
                                    defaultValue="accounts@"
                                    aria-invalid
                                />
                            </FormField>
                            <FormField id="ds-disabled" label="Account code">
                                <Input
                                    id="ds-disabled"
                                    defaultValue="HD-0042"
                                    disabled
                                />
                            </FormField>
                            <div className="md:col-span-2">
                                <FormField id="ds-notes" label="Notes">
                                    <Textarea
                                        id="ds-notes"
                                        placeholder="Anything the team should know…"
                                    />
                                </FormField>
                            </div>
                            <div className="flex items-center gap-2">
                                <Checkbox id="ds-check" defaultChecked />
                                <Label htmlFor="ds-check">
                                    Email the customer when this changes
                                </Label>
                            </div>
                        </div>
                    </Panel>
                    <Panel title="Dialog">
                        <p className="mb-3 text-sm text-muted-foreground">
                            12px corners, a 14.5px title, and the actions in a
                            full-width footer on a divider.
                        </p>
                        <Dialog>
                            <DialogTrigger asChild>
                                <Button variant="outline">Open a dialog</Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogTitle>Rename customer</DialogTitle>
                                <DialogDescription>
                                    Invoices already sent keep the old name.
                                </DialogDescription>
                                <FormField id="ds-dialog-name" label="Name">
                                    <Input
                                        id="ds-dialog-name"
                                        defaultValue="Harbour Dental"
                                    />
                                </FormField>
                                <DialogFooter>
                                    <DialogClose asChild>
                                        <Button variant="outline">
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <DialogClose asChild>
                                        <Button>Save</Button>
                                    </DialogClose>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </Panel>
                </Section>

                <Section
                    id="patterns"
                    title="Patterns"
                    description="Compositions repeated across the app: the stat strip, segmented panels and dense tables."
                >
                    <div className="grid overflow-hidden rounded-xl bg-card shadow-card sm:grid-cols-2 lg:grid-cols-4">
                        {[
                            {
                                icon: Building2,
                                label: 'Active customers',
                                value: '48',
                                detail: '3 onboarding',
                                tone: 'text-muted-foreground',
                            },
                            {
                                icon: CircleDashed,
                                label: 'Open tickets',
                                value: '39',
                                detail: '7 assigned to you',
                                tone: 'text-muted-foreground',
                            },
                            {
                                icon: Check,
                                label: 'Paid',
                                value: '$12,940',
                                detail: 'this month',
                                tone: 'text-success',
                            },
                            {
                                icon: Clock,
                                label: 'Overdue',
                                value: '6',
                                detail: 'need attention',
                                tone: 'text-destructive',
                            },
                        ].map((stat) => (
                            <div
                                key={stat.label}
                                className="space-y-1 border-border p-4 not-last:border-b sm:nth-[odd]:border-r lg:border-b-0 lg:not-last:border-r"
                            >
                                <div className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                    <stat.icon className="size-3.5" />
                                    {stat.label}
                                </div>
                                <div
                                    className={cn(
                                        'text-xl font-semibold tabular-nums',
                                        stat.tone === 'text-destructive' &&
                                            stat.tone,
                                    )}
                                >
                                    {stat.value}
                                </div>
                                <div className={cn('text-xs', stat.tone)}>
                                    {stat.detail}
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="grid gap-4 lg:grid-cols-[1fr_20rem]">
                        <Panel
                            title="Customer onboarding"
                            action={
                                <Button variant="ghost" size="sm">
                                    All customers
                                </Button>
                            }
                        >
                            <div className="-mx-4 overflow-x-auto">
                                <table className="w-full min-w-[34rem] text-sm">
                                    <thead>
                                        <tr className="border-y text-left text-xs text-muted-foreground">
                                            <th className="px-4 py-2 font-normal">
                                                Customer
                                            </th>
                                            <th className="px-2 py-2 font-normal">
                                                Status
                                            </th>
                                            <th className="px-2 py-2 font-normal">
                                                Progress
                                            </th>
                                            <th className="px-2 py-2 font-normal">
                                                Due
                                            </th>
                                            <th className="px-4 py-2 text-right font-normal">
                                                Team
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {sampleCustomers.map((customer) => (
                                            <tr
                                                key={customer.name}
                                                className="h-10 transition-colors hover:bg-muted"
                                            >
                                                <td className="px-4 font-medium">
                                                    {customer.name}
                                                </td>
                                                <td className="px-2">
                                                    <StatusPill
                                                        tone={
                                                            statusStyles[
                                                                customer.status
                                                            ].tone
                                                        }
                                                    >
                                                        {
                                                            statusStyles[
                                                                customer.status
                                                            ].label
                                                        }
                                                    </StatusPill>
                                                </td>
                                                <td className="px-2">
                                                    <Progress
                                                        value={
                                                            customer.progress
                                                        }
                                                    />
                                                </td>
                                                <td className="px-2 text-muted-foreground">
                                                    {customer.due}
                                                </td>
                                                <td className="px-4">
                                                    <div className="flex justify-end -space-x-1">
                                                        {customer.team.map(
                                                            (name) => (
                                                                <Initials
                                                                    key={name}
                                                                    name={name}
                                                                />
                                                            ),
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </Panel>

                        <Panel
                            title="My tickets"
                            action={
                                <ToggleGroup
                                    type="single"
                                    size="sm"
                                    value={segment}
                                    onValueChange={(value) =>
                                        value && setSegment(value)
                                    }
                                    className="rounded-md bg-muted p-0.5"
                                >
                                    {['upcoming', 'overdue', 'done'].map(
                                        (value) => (
                                            <ToggleGroupItem
                                                key={value}
                                                value={value}
                                                className="h-6 rounded-sm! px-2 text-xs capitalize data-[state=on]:bg-card data-[state=on]:shadow-xs"
                                            >
                                                {value}
                                            </ToggleGroupItem>
                                        ),
                                    )}
                                </ToggleGroup>
                            }
                        >
                            <ul className="space-y-0.5">
                                {[
                                    {
                                        title: 'Replace reception printer',
                                        customer: 'Harbour Dental',
                                        tone: 'bg-info',
                                    },
                                    {
                                        title: 'VPN drops after update',
                                        customer: 'Westfield Legal',
                                        tone: 'bg-destructive',
                                    },
                                    {
                                        title: 'New starter laptop',
                                        customer: 'Coastal Medical',
                                        tone: 'bg-warning',
                                    },
                                ].map((ticket) => (
                                    <li
                                        key={ticket.title}
                                        className="-mx-2 flex items-center gap-2.5 rounded-md px-2 py-1.5 hover:bg-muted"
                                    >
                                        <span
                                            className={cn(
                                                'size-1.5 shrink-0 rounded-full',
                                                ticket.tone,
                                            )}
                                        />
                                        <span className="min-w-0 flex-1">
                                            <span className="block truncate text-sm">
                                                {ticket.title}
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {ticket.customer}
                                            </span>
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </Panel>
                    </div>
                </Section>

                <Section
                    id="feedback"
                    title="Feedback"
                    description="Empty, loading and error states. Say what belongs here and how to get started."
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        <EmptyState
                            title="No tickets here"
                            description="Open a ticket to start tracking work for this customer."
                            action={
                                <Button size="sm" variant="outline">
                                    <Plus /> Open ticket
                                </Button>
                            }
                        />
                        <Panel title="Loading">
                            <div className="space-y-3">
                                {[0, 1, 2].map((row) => (
                                    <div
                                        key={row}
                                        className="flex items-center gap-3"
                                    >
                                        <Skeleton className="size-5 rounded-full" />
                                        <Skeleton className="h-3 flex-1" />
                                        <Skeleton className="h-3 w-12" />
                                    </div>
                                ))}
                            </div>
                        </Panel>
                        <Alert>
                            <AlertCircle />
                            <AlertTitle>Sample data is on</AlertTitle>
                            <AlertDescription>
                                Customers and tickets marked as samples are
                                removed when you clear them.
                            </AlertDescription>
                        </Alert>
                        <Alert variant="destructive">
                            <AlertCircle />
                            <AlertTitle>Payment failed</AlertTitle>
                            <AlertDescription>
                                The card on file was declined. Update it to keep
                                invoices sending.
                            </AlertDescription>
                        </Alert>
                    </div>
                </Section>
            </div>
        </>
    );
}

DesignSystem.layout = {
    breadcrumbs: [
        {
            title: 'Design system',
            href: designSystem(),
        },
    ],
};
