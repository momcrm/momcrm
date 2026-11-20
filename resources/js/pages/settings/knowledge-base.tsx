import { Form, Head } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import { useState } from 'react';
import CategorySelect, {
    categoryWithDescendants,
} from '@/components/articles/category-select';
import ConfirmDelete from '@/components/confirm-delete';
import FormField from '@/components/form-field';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { destroy, store, update } from '@/routes/article-categories';
import type { ArticleCategory } from '@/types';

function CategoryDialog({
    open,
    onOpenChange,
    categories,
    category,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    categories: ArticleCategory[];
    category?: ArticleCategory;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogTitle>
                    {category ? `Edit ${category.name}` : 'New category'}
                </DialogTitle>
                <DialogDescription>
                    Filtering the knowledge base by a category includes the
                    categories inside it.
                </DialogDescription>
                <Form
                    {...(category ? update.form(category.id) : store.form())}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="space-y-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <FormField
                                id="category-name"
                                label="Name"
                                error={errors.name}
                            >
                                <Input
                                    id="category-name"
                                    name="name"
                                    defaultValue={category?.name ?? ''}
                                    placeholder="Networking"
                                    required
                                    autoFocus
                                />
                            </FormField>
                            <FormField
                                id="category-parent"
                                label="Inside"
                                error={errors.parent_id}
                            >
                                <CategorySelect
                                    id="category-parent"
                                    name="parent_id"
                                    categories={categories}
                                    emptyLabel="Nothing (a top category)"
                                    defaultValue={category?.parent_id ?? ''}
                                    excludeIds={
                                        category
                                            ? categoryWithDescendants(
                                                  category.id,
                                                  categories,
                                              )
                                            : undefined
                                    }
                                />
                            </FormField>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => onOpenChange(false)}
                                >
                                    Cancel
                                </Button>
                                <Button disabled={processing}>
                                    {processing && <Spinner />}
                                    {category
                                        ? 'Save category'
                                        : 'Add category'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function KnowledgeBaseSettings({
    categories,
}: {
    categories: ArticleCategory[];
}) {
    const [editing, setEditing] = useState<
        { category: ArticleCategory } | 'new' | null
    >(null);

    return (
        <>
            <Head title="Knowledge base" />

            <h1 className="sr-only">Knowledge base</h1>

            <div className="space-y-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Categories"
                        description="Where articles are filed. Categories can sit inside each other, such as Networking › Routers."
                    />
                    <Button
                        size="sm"
                        className="shrink-0"
                        onClick={() => setEditing('new')}
                    >
                        <Plus />
                        Add category
                    </Button>
                </div>

                {categories.length === 0 ? (
                    <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        No categories yet. Add a few, such as Networking,
                        Accounts or Onboarding, so articles are easy to find.
                    </p>
                ) : (
                    <ul className="divide-y rounded-lg border bg-card shadow-xs">
                        {categories.map((category) => (
                            <li
                                key={category.id}
                                className="flex items-center gap-2 py-1.5 pr-3"
                                style={{
                                    paddingLeft: `${0.75 + category.depth * 1.25}rem`,
                                }}
                            >
                                <span className="min-w-0 flex-1 truncate text-sm">
                                    {category.name}
                                </span>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label={`Edit ${category.name}`}
                                    onClick={() => setEditing({ category })}
                                >
                                    <Pencil />
                                </Button>
                                <ConfirmDelete
                                    action={destroy.form(category.id)}
                                    title={`Delete ${category.name}?`}
                                    description="Its articles and the categories inside it move up a level."
                                />
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <CategoryDialog
                key={
                    editing === null
                        ? 'closed'
                        : editing === 'new'
                          ? 'new'
                          : editing.category.id
                }
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
                categories={categories}
                category={
                    editing && editing !== 'new' ? editing.category : undefined
                }
            />
        </>
    );
}
