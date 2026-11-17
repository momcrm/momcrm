import {
    BookOpen,
    Box,
    Briefcase,
    Building2,
    CalendarDays,
    FileText,
    KeyRound,
    Monitor,
    Package,
    Shield,
    Truck,
    UsersRound,
    Wrench,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { hueStyle } from '@/lib/colors';
import { cn } from '@/lib/utils';

/**
 * Icons a custom object can pick, by the name stored on it.
 */
export const objectIcons: Record<string, LucideIcon> = {
    box: Box,
    package: Package,
    truck: Truck,
    key: KeyRound,
    file: FileText,
    building: Building2,
    wrench: Wrench,
    monitor: Monitor,
    people: UsersRound,
    calendar: CalendarDays,
    shield: Shield,
    book: BookOpen,
    briefcase: Briefcase,
};

/**
 * A custom object's icon on a square of its colour.
 */
export function ObjectIcon({
    icon,
    hue,
    size = 'md',
}: {
    icon: string | null;
    hue: number | null;
    size?: 'sm' | 'md' | 'lg';
}) {
    const Icon = objectIcons[icon ?? 'box'] ?? Box;

    return (
        <span
            aria-hidden
            className={cn(
                'hue-dot',
                'inline-flex shrink-0 items-center justify-center rounded-md text-white',
                size === 'sm' && 'size-4 rounded-[4px] [&>svg]:size-2.5',
                size === 'md' && 'size-7 [&>svg]:size-3.5',
                size === 'lg' && 'size-9 rounded-lg [&>svg]:size-4.5',
            )}
            style={hueStyle(hue)}
        >
            <Icon />
        </span>
    );
}
