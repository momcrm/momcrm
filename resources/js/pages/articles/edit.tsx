import { Head, Link, setLayoutProps, useForm } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { ArticleStatusPill } from '@/components/articles/article-badges';
import RichTextEditor from '@/components/articles/rich-text-editor';
import FormField from '@/components/form-field';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import UnsavedChangesBar, {
    countChanges,
} from '@/components/unsaved-changes-bar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { edit, index, show, update } from '@/routes/articles';
import type {
    Article,
    ArticleClassificationOption,
    ArticleStatus,
} from '@/types';

export default function ArticleEdit({
    article,
    classifications,
}: {
    article: Article;
    classifications: ArticleClassificationOption[];
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Knowledge base', href: index() },
            { title: article.title, href: show(article.id) },
            { title: 'Edit', href: edit(article.id) },
        ],
    });

    const form = useForm({
        title: article.title,
        summary: article.summary ?? '',
        body: article.body,
        classification: article.classification as string,
    });
    const [saved, setSaved] = useState(form.data);
    const changes = countChanges(form.data, saved);
    const isPublished = article.status === 'published';

    const save = (status: ArticleStatus = article.status) => {
        form.transform((data) => ({ ...data, status }));
        form.put(update.url(article.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.setDefaults();
                setSaved(form.data);
            },
        });
    };
    const saveRef = useRef(save);
    saveRef.current = save;

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (
                event.key.toLowerCase() === 's' &&
                (event.metaKey || event.ctrlKey)
            ) {
                event.preventDefault();
                saveRef.current();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        save();
    };

    return (
        <>
            <Head title={`Edit ${article.title}`} />

            <form
                onSubmit={submit}
                className="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 md:p-6"
            >
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <ArticleStatusPill status={article.status} />
                    <div className="ml-auto flex flex-wrap items-center gap-1.5">
                        <Button variant="ghost" asChild>
                            <Link href={show(article.id)}>
                                <Eye />
                                View
                            </Link>
                        </Button>
                        {isPublished ? (
                            <Button
                                type="button"
                                variant="outline"
                                disabled={form.processing}
                                onClick={() => save('draft')}
                            >
                                Unpublish
                            </Button>
                        ) : (
                            <Button
                                type="button"
                                variant="outline"
                                disabled={form.processing}
                                onClick={() => save('draft')}
                            >
                                Save draft
                            </Button>
                        )}
                        <Button
                            type="button"
                            disabled={form.processing}
                            onClick={() => save('published')}
                        >
                            {form.processing && <Spinner />}
                            {isPublished ? 'Save' : 'Publish'}
                        </Button>
                    </div>
                </div>

                <div className="grid gap-1.5">
                    <Input
                        aria-label="Title"
                        value={form.data.title}
                        onChange={(event) =>
                            form.setData('title', event.target.value)
                        }
                        className="h-auto border-transparent px-0 text-[23px] font-semibold tracking-[-0.022em] shadow-none focus-visible:border-transparent focus-visible:ring-0"
                        placeholder="Title"
                        required
                    />
                    <InputError message={form.errors.title} />
                </div>

                <div className="grid gap-5 sm:grid-cols-[1fr_14rem]">
                    <FormField
                        id="summary"
                        label="Summary"
                        hint="A sentence saying what it's for, shown on the list."
                        error={form.errors.summary}
                    >
                        <Textarea
                            id="summary"
                            rows={2}
                            value={form.data.summary}
                            onChange={(event) =>
                                form.setData('summary', event.target.value)
                            }
                        />
                    </FormField>
                    <FormField
                        id="classification"
                        label="Who can read it"
                        hint={
                            classifications.find(
                                (option) =>
                                    option.value === form.data.classification,
                            )?.description
                        }
                        error={form.errors.classification}
                    >
                        <NativeSelect
                            id="classification"
                            value={form.data.classification}
                            onChange={(event) =>
                                form.setData(
                                    'classification',
                                    event.target.value,
                                )
                            }
                        >
                            {classifications.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </NativeSelect>
                    </FormField>
                </div>

                <div className="grid gap-1.5">
                    <RichTextEditor
                        value={form.data.body}
                        onChange={(html) => form.setData('body', html)}
                    />
                    <InputError message={form.errors.body} />
                </div>

                <UnsavedChangesBar
                    count={changes}
                    processing={form.processing}
                    onReset={() => {
                        form.reset();
                        form.clearErrors();
                    }}
                />
            </form>
        </>
    );
}
