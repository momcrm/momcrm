import { Form } from '@inertiajs/react';
import CategorySelect from '@/components/articles/category-select';
import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
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
import { store } from '@/routes/articles';
import type {
    ArticleCategory,
    ArticleClassificationOption,
    CustomerOption,
} from '@/types';

/**
 * Starts an article from its title and who may read it, then opens it in
 * the editor as a draft.
 */
export default function NewArticleDialog({
    open,
    onOpenChange,
    classifications,
    categories,
    customers = [],
    customer,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    classifications: ArticleClassificationOption[];
    categories: ArticleCategory[];
    /** Customers it could be about; not asked when `customer` is given. */
    customers?: CustomerOption[];
    /** The customer it is about, when started from their workspace. */
    customer?: CustomerOption;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogTitle>New article</DialogTitle>
                <DialogDescription>
                    {customer
                        ? `About ${customer.name}. It starts as a draft; publish it when it's ready.`
                        : "It starts as a draft. Publish it when it's ready."}
                </DialogDescription>
                <Form {...store.form()} className="space-y-5">
                    {({ errors, processing }) => (
                        <>
                            <FormField
                                id="article-title"
                                label="Title"
                                error={errors.title}
                            >
                                <Input
                                    id="article-title"
                                    name="title"
                                    placeholder="Reset a customer's router"
                                    required
                                    autoFocus
                                />
                            </FormField>
                            <FormField
                                id="article-classification"
                                label="Who can read it"
                                error={errors.classification}
                            >
                                <NativeSelect
                                    id="article-classification"
                                    name="classification"
                                    defaultValue="internal"
                                >
                                    {classifications.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label} ·{' '}
                                            {option.description}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                            {customer ? (
                                <input
                                    type="hidden"
                                    name="customer_id"
                                    value={customer.id}
                                />
                            ) : (
                                customers.length > 0 && (
                                    <FormField
                                        id="article-customer"
                                        label="About a customer"
                                        error={errors.customer_id}
                                    >
                                        <NativeSelect
                                            id="article-customer"
                                            name="customer_id"
                                            defaultValue=""
                                        >
                                            <option value="">
                                                No, for the whole team
                                            </option>
                                            {customers.map((option) => (
                                                <option
                                                    key={option.id}
                                                    value={option.id}
                                                >
                                                    {option.name}
                                                </option>
                                            ))}
                                        </NativeSelect>
                                    </FormField>
                                )
                            )}
                            {categories.length > 0 && (
                                <FormField
                                    id="article-category"
                                    label="Category"
                                    error={errors.category_id}
                                >
                                    <CategorySelect
                                        id="article-category"
                                        name="category_id"
                                        categories={categories}
                                        defaultValue=""
                                    />
                                </FormField>
                            )}
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
                                    Start writing
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
