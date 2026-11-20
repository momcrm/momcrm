import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import ArticleTable from '@/components/articles/article-table';
import NewArticleDialog from '@/components/articles/new-article-dialog';
import { TabHeader } from '@/components/customers/workspace-tab';
import EmptyState from '@/components/empty-state';
import Pager from '@/components/pager';
import SearchInput from '@/components/search-input';
import { Button } from '@/components/ui/button';
import { useWorkspaceBreadcrumbs } from '@/layouts/customer/layout';
import { visitWithQuery } from '@/lib/query';
import { articles as articlesTab } from '@/routes/customers';
import type {
    Abilities,
    ArticleCategory,
    ArticleClassificationOption,
    ArticleListItem,
    CustomerWorkspace,
    Paginated,
} from '@/types';

const RELOAD = ['articles', 'filters'];

export default function CustomerArticles({
    workspace,
    articles,
    filters,
    categories,
    classifications,
    articleCan,
}: {
    workspace: CustomerWorkspace;
    articles: Paginated<ArticleListItem>;
    filters: {
        search: string | null;
        sort: string;
        direction: 'asc' | 'desc';
    };
    categories: ArticleCategory[];
    classifications: ArticleClassificationOption[];
    articleCan: Abilities;
}) {
    const { customer } = workspace;
    useWorkspaceBreadcrumbs(customer, {
        title: 'Articles',
        href: articlesTab(customer.id),
    });
    const [isCreating, setIsCreating] = useState(false);

    return (
        <>
            <Head title={`${customer.name} · Articles`} />
            <TabHeader
                title="Articles"
                description="Knowledge base articles about this customer, such as how their network is set up."
                action={
                    articleCan.create && (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => setIsCreating(true)}
                        >
                            <Plus />
                            New article
                        </Button>
                    )
                }
            />

            <div className="mb-4">
                <SearchInput
                    value={filters.search}
                    placeholder="Search titles and text"
                    only={RELOAD}
                />
            </div>

            {articles.data.length === 0 ? (
                filters.search ? (
                    <EmptyState
                        title="No matching articles"
                        description="Check the spelling, or clear the search."
                    />
                ) : (
                    <EmptyState
                        title="No articles about this customer"
                        description="Write down what's particular to them: their network, their logins, who to call."
                    />
                )
            ) : (
                <>
                    <ArticleTable
                        articles={articles.data}
                        categories={categories}
                        showsCustomer={false}
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

            <NewArticleDialog
                open={isCreating}
                onOpenChange={setIsCreating}
                classifications={classifications}
                categories={categories}
                customer={{ id: customer.id, name: customer.name }}
            />
        </>
    );
}
