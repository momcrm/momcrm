import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';
import { visitWithQuery } from '@/lib/query';
import { cn } from '@/lib/utils';

/**
 * Filters the current page by a `search` query parameter as the user types,
 * keeping the page's other query parameters.
 */
export default function SearchInput({
    value,
    placeholder,
    only,
    size = 'default',
}: {
    value: string | null;
    placeholder: string;
    only: string[];
    /** `sm` is the compact field for a view's toolbar. */
    size?: 'default' | 'sm';
}) {
    const [term, setTerm] = useState(value ?? '');
    const isFirstRender = useRef(true);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            visitWithQuery({ search: term }, { only });
        }, 250);

        return () => clearTimeout(timeout);
    }, [term]); // eslint-disable-line react-hooks/exhaustive-deps

    return (
        <div
            className={cn(
                'relative w-full',
                size === 'sm' ? 'sm:w-[200px]' : 'max-w-sm',
            )}
        >
            <Search
                className={cn(
                    'absolute top-1/2 -translate-y-1/2',
                    size === 'sm'
                        ? 'left-[9px] size-3.5 text-subtle-foreground'
                        : 'left-3 size-4 text-muted-foreground',
                )}
            />
            <Input
                type="search"
                value={term}
                onChange={(event) => setTerm(event.target.value)}
                placeholder={placeholder}
                className={cn(
                    size === 'sm'
                        ? 'h-[26px] pl-[30px] text-[12.5px] md:text-[12.5px]'
                        : 'pl-9',
                )}
                aria-label={placeholder}
            />
        </div>
    );
}
