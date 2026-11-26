import { ChevronRight } from 'lucide-react';
import { SidebarGroupLabel, useSidebar } from '@/components/ui/sidebar';
import { useFoldedSection } from '@/hooks/use-folded-section';
import { cn } from '@/lib/utils';

const labelClasses =
    'h-7 px-2 text-[11px] font-medium tracking-wide text-subtle-foreground uppercase';

/**
 * Whether a section of the sidebar is folded away. A sidebar collapsed to
 * icons has no headings to unfold with, so nothing is folded there.
 */
export function useNavSectionFold(key: string): {
    isFolded: boolean;
    toggle: () => void;
} {
    const { state, isMobile } = useSidebar();
    const [isFolded, toggle] = useFoldedSection(key);

    return {
        isFolded: isFolded && (isMobile || state === 'expanded'),
        toggle,
    };
}

/**
 * The heading above a section of the sidebar. Given a way to fold, it is a
 * button that folds the section away and brings it back.
 */
export function NavSectionLabel({
    title,
    fold,
}: {
    title: string;
    fold?: { isFolded: boolean; toggle: () => void };
}) {
    if (!fold) {
        return (
            <SidebarGroupLabel className={labelClasses}>
                {title}
            </SidebarGroupLabel>
        );
    }

    return (
        <SidebarGroupLabel asChild className={labelClasses}>
            <button
                type="button"
                aria-expanded={!fold.isFolded}
                onClick={fold.toggle}
                className="group/label w-full gap-1 text-left hover:text-foreground"
            >
                {title}
                <ChevronRight
                    aria-hidden
                    className={cn(
                        'size-3! transition-[rotate,opacity]',
                        fold.isFolded
                            ? 'opacity-100'
                            : 'rotate-90 opacity-0 group-hover/label:opacity-100 group-focus-visible/label:opacity-100',
                    )}
                />
            </button>
        </SidebarGroupLabel>
    );
}
