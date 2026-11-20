import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import ArticleTable from '@/components/articles/article-table';
import CategorySelect from '@/components/articles/category-select';
import NewArticleDialog from '@/components/articles/new-article-dialog';
import EmptyState from '@/components/empty-state';
import { useLabels } from '@/components/labels';
import NativeSelect from '@/components/native-select';
import PageHeader from '@/components/page-header';
import Pager from '@/components/pager';
import SearchInput from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { visitWithQuery } from '@/lib/query';
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
    const labels = useLabels(filters.label);
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

    return (
        <>
            <Head title="Knowledge base" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Knowledge base"
                    description="How-tos and notes your team relies on. Confidential articles are only shown to the roles that may read them."
                    actions={
                        can.create && (
                            <Button onClick={() => setIsCreating(true)}>
                                <Plus />
                                New article
                            </Button>
                        )
                    }
                />

                <div className="flex flex-wrap items-center gap-2">
                    <SearchInput
                        value={filters.search}
                        placeholder="Search titles and text"
                        only={RELOAD}
                    />
                    <NativeSelect
                        aria-label="Status"
                        value={filters.status ?? ''}
                        onChange={(event) =>
                            filter('status')(event.target.value)
                        }
                        className="h-8 w-auto text-[13px]"
                    >
                        <option value="">Drafts and published</option>
                        {statuses.map((status) => (
                            <option key={status.value} value={status.value}>
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
                            className="h-8 w-auto max-w-56 text-[13px]"
                        >
                            <option value="">Any customer</option>
                            {customers.map((customer) => (
                                <option key={customer.id} value={customer.id}>
                                    {customer.name}
                                </option>
                            ))}
                        </NativeSelect>
                    )}
                    {categories.length > 0 && (
                        <CategorySelect
                            aria-label="Category"
                            categories={categories}
                            emptyLabel="Any category"
                            value={filters.category ?? ''}
                            onChange={(event) =>
                                filter('category')(event.target.value)
                            }
                            className="h-8 w-auto text-[13px]"
                        />
                    )}
                    {labels.length > 0 && (
                        <NativeSelect
                            aria-label="Label"
                            value={filters.label ?? ''}
                            onChange={(event) =>
                                filter('label')(event.target.value)
                            }
                            className="h-8 w-auto text-[13px]"
                        >
                            <option value="">Any label</option>
                            {labels.map((label) => (
                                <option key={label.id} value={label.id}>
                                    {label.name}
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
                        className="h-8 w-auto text-[13px]"
                    >
                        <option value="">Any review date</option>
                        <option value="due">Review due</option>
                    </NativeSelect>
                    {classifications.length > 1 && (
                        <NativeSelect
                            aria-label="Classification"
                            value={filters.classification ?? ''}
                            onChange={(event) =>
                                filter('classification')(event.target.value)
                            }
                            className="h-8 w-auto text-[13px]"
                        >
                            <option value="">Any classification</option>
                            {classifications.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </NativeSelect>
                    )}
                </div>

                {articles.data.length === 0 ? (
                    isFiltered ? (
                        <EmptyState
                            title="No matching articles"
                            description="Check the spelling, or clear a filter."
                        />
                    ) : (
                        <EmptyState
                            title="No articles yet"
                            description="Write down how your team does things: setting up a laptop, resetting a router, who to call when the internet drops."
                        />
                    )
                ) : (
                    <>
                        <ArticleTable
                            articles={articles.data}
                            categories={categories}
                            sort={{
                                column: filters.sort,
                                direction: filters.direction,
                            }}
                            onSortChange={({ column, direction }) =>
                                visitWithQuery(
                                    { sort: column, direction },
                                    { only: RELOAD },
                                )
                            }
                        />
                        <Pager page={articles} only="articles" />
                    </>
                )}
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

ArticlesIndex.layout = {
    breadcrumbs: [{ title: 'Knowledge base', href: index() }],
};
