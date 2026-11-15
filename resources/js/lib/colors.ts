import type { ColorName } from '@/types';

/**
 * Swatch classes for the colours projects, groups and labels are tagged with.
 * Literal class names so Tailwind picks them up.
 */
export const colorDot: Record<ColorName, string> = {
    amber: 'bg-amber-500',
    blue: 'bg-blue-500',
    green: 'bg-emerald-500',
    indigo: 'bg-indigo-500',
    rose: 'bg-rose-500',
    slate: 'bg-slate-500',
    teal: 'bg-teal-500',
    violet: 'bg-violet-500',
};

/**
 * Text colours for icons tinted with a choice's colour.
 */
export const colorText: Record<ColorName, string> = {
    amber: 'text-amber-600 dark:text-amber-400',
    blue: 'text-blue-600 dark:text-blue-400',
    green: 'text-emerald-600 dark:text-emerald-400',
    indigo: 'text-indigo-600 dark:text-indigo-400',
    rose: 'text-rose-600 dark:text-rose-400',
    slate: 'text-subtle-foreground',
    teal: 'text-teal-600 dark:text-teal-400',
    violet: 'text-violet-600 dark:text-violet-400',
};

export const colorSoft: Record<ColorName, string> = {
    amber: 'bg-amber-500/12 text-amber-700 dark:text-amber-300',
    blue: 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
    green: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    indigo: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
    rose: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    slate: 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
    teal: 'bg-teal-500/10 text-teal-700 dark:text-teal-300',
    violet: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
};
