import { Head } from '@inertiajs/react';
import { BookOpen, CalendarClock, Folder, Plus, SearchX } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import ArticleList from '@/components/articles/article-list';
import CategorySelect from '@/components/articles/category-select';
import NewArticleDialog from '@/components/articles/new-article-dialog';
import EmptyState from '@/components/empty-state';
import { useLabels } from '@/components/labels';
import NativeSelect from '@/components/native-select';
import Pager from '@/components/pager';
import SearchInput from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { hueStyle } from '@/lib/colors';
import { visitWithQuery } from '@/lib/query';
import { cn } from '@/lib/utils';
import { index } from '@/routes/articles';
import type {
    Abilities,
    ArticleCategory,
    ArticleClassificationOption,
    ArticleListItem,
    CustomerOption,
    Option,
    Paginated,
} from '@/types';

type Filters = {
    search: string | null;
    status: string | null;
    classification: string | null;
    category: string | null;
    label: string | null;
    review: string | null;
    customer: string | null;
    sort: string;
    direction: 'asc' | 'desc';
};

const RELOAD = ['articles', 'filters'];

const SORTS: { value: string; label: string }[] = [
    { value: 'updated:desc', label: 'Recently updated' },
    { value: 'updated:asc', label: 'Least recently updated' },
    { value: 'title:asc', label: 'Title, A to Z' },
    { value: 'title:desc', label: 'Title, Z to A' },
];

const FILTER_CLASS = 'h-8 w-auto max-w-56 text-[13px]';

export default function ArticlesIndex({
    articles,
    filters,
    statuses,
    categories,
    customers,
    classifications,
    can,
}: {
    articles: Paginated<ArticleListItem>;
    filters: Filters;
    statuses: Option[];
    categories: ArticleCategory[];
    customers: CustomerOption[];
    classifications: ArticleClassificationOption[];
    can: Abilities;
}) {
    const [isCreating, setIsCreating] = useState(false);
    /** Bumped to empty the search box once the filters are cleared. */
    const [searchKey, setSearchKey] = useState(0);
    const labels = useLabels(filters.label);
    const categoryPaths = useMemo(
        () =>
            new Map(categories.map((category) => [category.id, category.path])),
        [categories],
    );
    const isFiltered = Boolean(
        filters.search ||
        filters.status ||
        filters.classification ||
        filters.category ||
        filters.label ||
        filters.review ||
        filters.customer,
    );
    const filter = (key: string) => (value: string) =>
        visitWithQuery({ [key]: value }, { only: RELOAD });
    const clearFilters = () =>
        visitWithQuery(
            {
                search: null,
                status: null,
                classification: null,
                category: null,
                label: null,
                review: null,
                customer: null,
            },
            { only: RELOAD, onSuccess: () => setSearchKey((key) => key + 1) },
        );
    const heading = filters.review
        ? 'Review due'
        : filters.category
          ? (categoryPaths.get(filters.category) ?? 'All articles')
          : 'All articles';

    return (
        <>
            <Head title="Knowledge base" />

            <div className="mx-auto w-full max-w-6xl px-4 py-6 md:px-6 md:py-8">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div className="max-w-3xl">
                        <h1 className="text-[26px] leading-tight font-semibold tracking-[-0.025em] md:text-[30px]">
                            Knowledge base
                        </h1>
                        <p className="mt-1.5 text-[14px] leading-relaxed text-soft-foreground">
                            How-tos and notes your team relies on. Confidential
                            articles are only shown to the roles that may read
                            them.
                        </p>
                    </div>
                    {can.create && (
                        <Button onClick={() => setIsCreating(true)}>
                            <Plus />
                            New article
                        </Button>
                    )}
                </header>

                <div className="mt-6 grid gap-6 lg:grid-cols-[13.5rem_minmax(0,1fr)] lg:gap-8">
                    <nav
                        aria-label="Knowledge base sections"
                        className="hidden space-y-5 lg:sticky lg:top-[calc(46px+1.5rem)] lg:block lg:max-h-[calc(100svh-46px-3rem)] lg:[scrollbar-width:thin] lg:self-start lg:overflow-y-auto"
                    >
                        <div className="space-y-0.5">
                            <RailItem
                                icon={<BookOpen />}
                                isActive={!filters.category && !filters.review}
                                onClick={() =>
                                    visitWithQuery(
                                        { category: null, review: null },
                                        { only: RELOAD },
                                    )
                                }
                            >
                                All articles
                            </RailItem>
                            <RailItem
                                icon={<CalendarClock />}
                                isActive={Boolean(filters.review)}
                                onClick={() =>
                                    filter('review')(
                                        filters.review ? '' : 'due',
                                    )
                                }
                            >
                                Review due
                            </RailItem>
                        </div>

                        {categories.length > 0 && (
                            <div>
                                <h2 className="mb-1 px-2.5 text-[11.5px] font-medium tracking-wide text-subtle-foreground uppercase">
                                    Categories
                                </h2>
                                <div className="space-y-0.5">
                                    {categories.map((category) => (
                                        <RailItem
                                            key={category.id}
                                            icon={<Folder />}
                                            depth={category.depth}
                                            isActive={
                                                filters.category === category.id
                                            }
                                            onClick={() =>
                                                filter('category')(
                                                    filters.category ===
                                                        category.id
                                                        ? ''
                                                        : category.id,
                                                )
                                            }
                                        >
                                            {category.name}
                                        </RailItem>
                                    ))}
                                </div>
                            </div>
                        )}

                        {labels.length > 0 && (
                            <div>
                                <h2 className="mb-1 px-2.5 text-[11.5px] font-medium tracking-wide text-subtle-foreground uppercase">
                                    Labels
                                </h2>
                                <div className="space-y-0.5">
                                    {labels.map((label) => (
                                        <RailItem
                                            key={label.id}
                                            icon={
                                                <span
                                                    aria-hidden
                                                    className="mx-[5px] size-2 shrink-0 rounded-[3px] hue-dot"
                                                    style={hueStyle(label.hue)}
                                                />
                                            }
                                            isActive={
                                                filters.label === label.id
                                            }
                                            onClick={() =>
                                                filter('label')(
                                                    filters.label === label.id
                                                        ? ''
                                                        : label.id,
                                                )
                                            }
                                        >
                                            {label.name}
                                        </RailItem>
                                    ))}
                                </div>
                            </div>
                        )}
                    </nav>

                    <div className="min-w-0 space-y-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <div className="w-full sm:w-auto sm:min-w-64 sm:flex-1">
                                <SearchInput
                                    key={searchKey}
                                    value={filters.search}
                                    placeholder="Search titles and text"
                                    only={RELOAD}
                                />
                            </div>
                            {categories.length > 0 && (
                                <CategorySelect
                                    aria-label="Category"
                                    categories={categories}
                                    emptyLabel="Any category"
                                    value={filters.category ?? ''}
                                    onChange={(event) =>
                                        filter('category')(event.target.value)
                                    }
                                    className={cn(FILTER_CLASS, 'lg:hidden')}
                                />
                            )}
                            <NativeSelect
                                aria-label="Status"
                                value={filters.status ?? ''}
                                onChange={(event) =>
                                    filter('status')(event.target.value)
                                }
                                className={FILTER_CLASS}
                            >
                                <option value="">Drafts and published</option>
                                {statuses.map((status) => (
                                    <option
                                        key={status.value}
                                        value={status.value}
                                    >
                                        {status.label}
                                    </option>
                                ))}
                            </NativeSelect>
                            {customers.length > 0 && (
                                <NativeSelect
                                    aria-label="Customer"
                                    value={filters.customer ?? ''}
                                    onChange={(event) =>
                                        filter('customer')(event.target.value)
                                    }
                                    className={FILTER_CLASS}
                                >
                                    <option value="">Any customer</option>
                                    {customers.map((customer) => (
                                        <option
                                            key={customer.id}
                                            value={customer.id}
                                        >
                                            {customer.name}
                                        </option>
                                    ))}
                                </NativeSelect>
                            )}
                            {labels.length > 0 && (
                                <NativeSelect
                                    aria-label="Label"
                                    value={filters.label ?? ''}
                                    onChange={(event) =>
                                        filter('label')(event.target.value)
                                    }
                                    className={cn(FILTER_CLASS, 'lg:hidden')}
                                >
                                    <option value="">Any label</option>
                                    {labels.map((label) => (
                                        <option key={label.id} value={label.id}>
                                            {label.name}
                                        </option>
                                    ))}
                                </NativeSelect>
                            )}
                            {classifications.length > 1 && (
                                <NativeSelect
                                    aria-label="Audience"
                                    value={filters.classification ?? ''}
                                    onChange={(event) =>
                                        filter('classification')(
                                            event.target.value,
                                        )
                                    }
                                    className={FILTER_CLASS}
                                >
                                    <option value="">Any audience</option>
                                    {classifications.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            )}
                            <NativeSelect
                                aria-label="Review"
                                value={filters.review ?? ''}
                                onChange={(event) =>
                                    filter('review')(event.target.value)
                                }
                                className={cn(FILTER_CLASS, 'lg:hidden')}
                            >
                                <option value="">Any review date</option>
                                <option value="due">Review due</option>
                            </NativeSelect>
                        </div>

                        {articles.data.length === 0 ? (
                            isFiltered ? (
                                <EmptyState
                                    icon={SearchX}
                                    title="No matching articles"
                                    description="Check the spelling, or clear a filter."
                                    action={
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={clearFilters}
                                        >
                                            Clear filters
                                        </Button>
                                    }
                                />
                            ) : (
                                <EmptyState
                                    icon={BookOpen}
                                    title="No articles yet"
                                    description="Write down how your team does things: setting up a laptop, resetting a router, who to call when the internet drops."
                                    action={
                                        can.create && (
                                            <Button
                                                size="sm"
                                                onClick={() =>
                                                    setIsCreating(true)
                                                }
                                            >
                                                <Plus />
                                                Write the first one
                                            </Button>
                                        )
                                    }
                                />
                            )
                        ) : (
                            <>
                                <ArticleList
                                    articles={articles.data}
                                    categoryPaths={categoryPaths}
                                    header={
                                        <>
                                            <p className="min-w-0 truncate text-[13px]">
                                                <span className="font-medium">
                                                    {heading}
                                                </span>
                                                <span className="text-muted-foreground">
                                                    {' · '}
                                                    {articles.total}{' '}
                                                    {articles.total === 1
                                                        ? 'article'
                                                        : 'articles'}
                                                </span>
                                                {isFiltered && (
                                                    <button
                                                        type="button"
                                                        onClick={clearFilters}
                                                        className="ml-2 text-muted-foreground underline-offset-2 hover:text-foreground hover:underline"
                                                    >
                                                        Clear filters
                                                    </button>
                                                )}
                                            </p>
                                            <NativeSelect
                                                aria-label="Sort"
                                                value={`${filters.sort}:${filters.direction}`}
                                                onChange={(event) => {
                                                    const [sort, direction] =
                                                        event.target.value.split(
                                                            ':',
                                                        );
                                                    visitWithQuery(
                                                        { sort, direction },
                                                        { only: RELOAD },
                                                    );
                                                }}
                                                className="h-7 w-auto border-transparent bg-transparent text-[12.5px] shadow-none hover:bg-muted"
                                            >
                                                {SORTS.map((sort) => (
                                                    <option
                                                        key={sort.value}
                                                        value={sort.value}
                                                    >
                                                        {sort.label}
                                                    </option>
                                                ))}
                                            </NativeSelect>
                                        </>
                                    }
                                />
                                <Pager page={articles} only="articles" />
                            </>
                        )}
                    </div>
                </div>
            </div>

            <NewArticleDialog
                open={isCreating}
                onOpenChange={setIsCreating}
                classifications={classifications}
                categories={categories}
                customers={customers}
            />
        </>
    );
}

/** A section of the knowledge base in the rail beside the list. */
function RailItem({
    icon,
    depth = 0,
    isActive,
    onClick,
    children,
}: {
    icon: ReactNode;
    depth?: number;
    isActive: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-current={isActive ? 'page' : undefined}
            style={{ paddingLeft: `${0.625 + depth * 0.875}rem` }}
            className={cn(
                'flex h-8 w-full items-center gap-2 rounded-md pr-2.5 text-left text-[13px] transition-colors [&_svg]:size-4 [&_svg]:shrink-0',
                isActive
                    ? 'bg-card font-medium text-foreground shadow-xs ring-1 ring-border [&_svg]:text-primary'
                    : 'text-soft-foreground hover:bg-muted hover:text-foreground [&_svg]:text-subtle-foreground',
            )}
        >
            {icon}
            <span className="truncate">{children}</span>
        </button>
    );
}

ArticlesIndex.layout = {
    breadcrumbs: [{ title: 'Knowledge base', href: index() }],
};
