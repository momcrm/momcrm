import { Form } from '@inertiajs/react';
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
import type { ArticleClassificationOption } from '@/types';

/**
 * Starts an article from its title and who may read it, then opens it in
 * the editor as a draft.
 */
export default function NewArticleDialog({
    open,
    onOpenChange,
    classifications,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    classifications: ArticleClassificationOption[];
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogTitle>New article</DialogTitle>
                <DialogDescription>
                    It starts as a draft. Publish it when it's ready.
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
