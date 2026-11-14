import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

/**
 * Previous/next links for a paginated prop, reloading only that prop.
 */
export default function Pager<T>({
    page,
    only,
}: {
    page: Paginated<T>;
    only: string;
}) {
    if (page.last_page <= 1) {
        return null;
    }

    return (
        <div className="flex items-center justify-between pt-2 text-sm text-muted-foreground">
            <span>
                Page {page.current_page} of {page.last_page}
            </span>
            <div className="flex gap-2">
                {[
                    { label: 'Previous', url: page.prev_page_url },
                    { label: 'Next', url: page.next_page_url },
                ].map(({ label, url }) => (
                    <Button
                        key={label}
                        variant="outline"
                        size="sm"
                        disabled={!url}
                        asChild={Boolean(url)}
                    >
                        {url ? (
                            <Link
                                href={url}
                                only={[only]}
                                preserveScroll
                                preserveState
                            >
                                {label}
                            </Link>
                        ) : (
                            label
                        )}
                    </Button>
                ))}
            </div>
        </div>
    );
}
