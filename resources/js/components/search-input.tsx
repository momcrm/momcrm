import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';

/**
 * Filters the current page by a `search` query parameter as the user types.
 */
export default function SearchInput({
    value,
    placeholder,
    only,
}: {
    value: string | null;
    placeholder: string;
    only: string[];
}) {
    const [term, setTerm] = useState(value ?? '');
    const isFirstRender = useRef(true);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(window.location.pathname, term ? { search: term } : {}, {
                only,
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 250);

        return () => clearTimeout(timeout);
    }, [term]); // eslint-disable-line react-hooks/exhaustive-deps

    return (
        <div className="relative w-full max-w-sm">
            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
                type="search"
                value={term}
                onChange={(event) => setTerm(event.target.value)}
                placeholder={placeholder}
                className="pl-9"
                aria-label={placeholder}
            />
        </div>
    );
}
