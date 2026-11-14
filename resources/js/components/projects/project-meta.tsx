import {
    BookOpen,
    Briefcase,
    Building2,
    FolderKanban,
    Headset,
    Lock,
    Rocket,
    Shield,
    Star,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import StatusPill from '@/components/status-pill';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useInitials } from '@/hooks/use-initials';
import { colorDot } from '@/lib/colors';
import { cn } from '@/lib/utils';
import type { ColorName, CustomerOption, ProjectStatus, Tone } from '@/types';

export const projectStatuses: {
    value: ProjectStatus;
    label: string;
    tone: Tone;
}[] = [
    { value: 'planning', label: 'Planning', tone: 'neutral' },
    { value: 'in_progress', label: 'In progress', tone: 'primary' },
    { value: 'at_risk', label: 'At risk', tone: 'destructive' },
    { value: 'on_hold', label: 'On hold', tone: 'warning' },
    { value: 'completed', label: 'Completed', tone: 'success' },
];

export function projectStatusOf(status: ProjectStatus) {
    return (
        projectStatuses.find((option) => option.value === status) ??
        projectStatuses[0]
    );
}

export function ProjectStatusPill({ status }: { status: ProjectStatus }) {
    const { label, tone } = projectStatusOf(status);

    return <StatusPill tone={tone}>{label}</StatusPill>;
}

/**
 * Icons a project can pick, by the name stored on it.
 */
export const projectIcons: Record<string, LucideIcon> = {
    folder: FolderKanban,
    building: Building2,
    shield: Shield,
    book: BookOpen,
    rocket: Rocket,
    briefcase: Briefcase,
    headset: Headset,
};

export function ProjectIcon({
    icon,
    color,
    size = 'md',
}: {
    icon: string | null;
    color: ColorName;
    size?: 'sm' | 'md' | 'lg';
}) {
    const Icon = projectIcons[icon ?? 'folder'] ?? FolderKanban;

    return (
        <span
            aria-hidden
            className={cn(
                'inline-flex shrink-0 items-center justify-center rounded-md text-white',
                colorDot[color],
                size === 'sm' && 'size-4 rounded-[4px] [&>svg]:size-2.5',
                size === 'md' && 'size-7 [&>svg]:size-3.5',
                size === 'lg' && 'size-9 rounded-lg [&>svg]:size-4.5',
            )}
        >
            <Icon />
        </span>
    );
}

export function RestrictedLock() {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Lock
                    aria-label="Members only"
                    className="size-3 shrink-0 text-subtle-foreground"
                />
            </TooltipTrigger>
            <TooltipContent>Members only</TooltipContent>
        </Tooltip>
    );
}

export function StarMark({ isFavorite }: { isFavorite: boolean }) {
    return (
        <Star
            className={cn(
                'size-3.5',
                isFavorite
                    ? 'fill-amber-400 text-amber-400'
                    : 'text-subtle-foreground',
            )}
        />
    );
}

/**
 * Overlapping initials for a project's members, with a "+n" for the rest.
 */
export function MemberAvatars({
    members,
    max = 4,
}: {
    members: CustomerOption[];
    max?: number;
}) {
    const getInitials = useInitials();
    const shown = members.slice(0, max);
    const rest = members.length - shown.length;

    return (
        <span className="flex -space-x-1.5">
            {shown.map((member) => (
                <Tooltip key={member.id}>
                    <TooltipTrigger asChild>
                        <span className="inline-flex size-6 items-center justify-center rounded-full bg-accent text-[10px] font-semibold text-muted-foreground ring-2 ring-card">
                            {getInitials(member.name)}
                        </span>
                    </TooltipTrigger>
                    <TooltipContent>{member.name}</TooltipContent>
                </Tooltip>
            ))}
            {rest > 0 && (
                <span className="inline-flex size-6 items-center justify-center rounded-full bg-muted text-[10px] text-muted-foreground ring-2 ring-card">
                    +{rest}
                </span>
            )}
        </span>
    );
}

/**
 * Closed tickets out of all of them, as a thin bar and a percentage.
 */
export function ProgressBar({
    closed,
    total,
    showLabel = true,
}: {
    closed: number;
    total: number;
    showLabel?: boolean;
}) {
    const percent = total === 0 ? 0 : Math.round((closed / total) * 100);

    return (
        <span className="flex items-center gap-2">
            <span
                role="progressbar"
                aria-valuenow={percent}
                aria-valuemin={0}
                aria-valuemax={100}
                className="h-1.5 flex-1 overflow-hidden rounded-full bg-muted"
            >
                <span
                    className="block h-full rounded-full bg-primary"
                    style={{ width: `${percent}%` }}
                />
            </span>
            {showLabel && (
                <span className="w-9 text-right text-xs text-muted-foreground tabular-nums">
                    {percent}%
                </span>
            )}
        </span>
    );
}
