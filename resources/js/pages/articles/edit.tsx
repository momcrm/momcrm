import { Head, Link, setLayoutProps, useForm, useHttp } from '@inertiajs/react';
import { ArrowLeft, Eye, FileText } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import type {
    FormEvent,
    KeyboardEvent as ReactKeyboardEvent,
    ReactNode,
} from 'react';
import { ArticleStatusPill } from '@/components/articles/article-badges';
import {
    ArticleSidebar,
    SidePanel,
} from '@/components/articles/article-sidebar';
import CategorySelect from '@/components/articles/category-select';
import { countWords, readingMinutes } from '@/components/articles/reading-time';
import RichTextEditor from '@/components/articles/rich-text-editor';
import FormField from '@/components/form-field';
import InputError from '@/components/input-error';
import { LabelField } from '@/components/labels';
import NativeSelect from '@/components/native-select';
import UnsavedChangesBar, {
    countChanges,
} from '@/components/unsaved-changes-bar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { edit, index, show, update } from '@/routes/articles';
import { store as storeImage } from '@/routes/articles/images';
import type {
    Article,
    ArticleCategory,
    ArticleClassificationOption,
    CustomerOption,
    ArticleStatus,
} from '@/types';

const isMac =
    typeof navigator !== 'undefined' && /Mac/.test(navigator.platform);

/**
 * The version a change will make: the next minor one, or the next major one.
 */
function nextVersion(current: string, isMajor: boolean): string {
    const [major, minor] = current.split('.').map(Number);

    return isMajor ? `${major + 1}.0` : `${major}.${minor + 1}`;
}

/** The title and summary fields: one line each, so Enter doesn't add another. */
function keepOnOneLine(event: ReactKeyboardEvent<HTMLTextAreaElement>) {
    if (event.key === 'Enter') {
        event.preventDefault();
    }
}

export default function ArticleEdit({
    article,
    categories,
    customers,
    classifications,
}: {
    article: Article;
    categories: ArticleCategory[];
    customers: CustomerOption[];
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
        category_id: article.category_id ?? '',
        customer_id: article.customer_id ?? '',
        label_ids: article.labels.map((label) => label.id),
        next_review_on: article.next_review_on ?? '',
    });
    const [saved, setSaved] = useState(form.data);
    const [changeNote, setChangeNote] = useState('');
    const [isMajor, setIsMajor] = useState(false);
    const isVersioned = article.version !== null;
    const imageUpload = useHttp<{ image: File | null }, { src: string }>({
        image: null,
    });
    const uploadImage = async (file: File) => {
        imageUpload.transform(() => ({ image: file }));
        const { src } = await imageUpload.post(storeImage.url(article.id));

        return src;
    };
    const changes = countChanges(form.data, saved);
    const isPublished = article.status === 'published';
    const words = useMemo(() => countWords(form.data.body), [form.data.body]);
    const classification = classifications.find(
        (option) => option.value === form.data.classification,
    );

    const save = (status: ArticleStatus = article.status) => {
        form.transform((data) => ({
            ...data,
            status,
            change_note: changeNote,
            is_major: isMajor,
            labels_sent: true,
        }));
        form.put(update.url(article.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.setDefaults();
                setSaved(form.data);
                setChangeNote('');
                setIsMajor(false);
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
                className="mx-auto w-full max-w-6xl px-4 py-6 md:px-8 md:py-10"
            >
                <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_17.5rem] xl:gap-14">
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <Link
                                href={show(article.id)}
                                className="flex items-center gap-1.5 text-[13px] text-muted-foreground hover:text-foreground"
                            >
                                <ArrowLeft className="size-3.5" />
                                Back to article
                            </Link>

                            <div className="ml-auto flex flex-wrap items-center gap-1.5">
                                <Button variant="ghost" asChild>
                                    <Link href={show(article.id)}>
                                        <Eye />
                                        View
                                    </Link>
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={form.processing}
                                    onClick={() => save('draft')}
                                >
                                    {isPublished ? 'Unpublish' : 'Save draft'}
                                </Button>
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

                        <div className="mt-6 border-b pb-6">
                            <textarea
                                aria-label="Title"
                                rows={1}
                                value={form.data.title}
                                onChange={(event) =>
                                    form.setData('title', event.target.value)
                                }
                                onKeyDown={keepOnOneLine}
                                placeholder="Untitled article"
                                required
                                className="field-sizing-content w-full resize-none overflow-hidden bg-transparent py-1 text-[28px] leading-[1.15] font-semibold tracking-[-0.025em] text-balance outline-none placeholder:text-subtle-foreground md:text-[34px]"
                            />
                            <InputError message={form.errors.title} />

                            <textarea
                                aria-label="Summary"
                                rows={1}
                                value={form.data.summary}
                                onChange={(event) =>
                                    form.setData('summary', event.target.value)
                                }
                                onKeyDown={keepOnOneLine}
                                placeholder="Add a sentence saying what it's for. It shows on the list."
                                className="mt-2 field-sizing-content w-full max-w-[38rem] resize-none overflow-hidden bg-transparent text-[16.5px] leading-relaxed text-pretty text-soft-foreground outline-none placeholder:text-subtle-foreground"
                            />
                            <InputError message={form.errors.summary} />
                        </div>

                        <div className="mt-8 grid gap-1.5">
                            <RichTextEditor
                                value={form.data.body}
                                onChange={(html) => form.setData('body', html)}
                                uploadImage={uploadImage}
                                contentClassName="article-body-reading min-h-[28rem] px-6 py-5 md:px-8 md:py-7"
                            />
                            <InputError message={form.errors.body} />
                        </div>
                    </div>

                    <ArticleSidebar>
                        <SidePanel title="Publishing">
                            <dl className="divide-y border-t text-[13px]">
                                <Row label="Status">
                                    <ArticleStatusPill
                                        status={article.status}
                                    />
                                </Row>
                                <Row label="Version">
                                    {isVersioned ? (
                                        <span className="tabular-nums">
                                            v{article.version}
                                            <span className="text-muted-foreground">
                                                {' → '}v
                                                {nextVersion(
                                                    article.version ?? '1.0',
                                                    isMajor,
                                                )}
                                            </span>
                                        </span>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            Not published yet
                                        </span>
                                    )}
                                </Row>
                                <Row label="Length">
                                    <span className="flex items-center gap-1.5 tabular-nums">
                                        <FileText className="size-3.5 text-muted-foreground" />
                                        {words.toLocaleString()}{' '}
                                        {words === 1 ? 'word' : 'words'}
                                        {words > 0 && (
                                            <span className="text-muted-foreground">
                                                ·{' '}
                                                {readingMinutes(form.data.body)}{' '}
                                                min
                                            </span>
                                        )}
                                    </span>
                                </Row>
                            </dl>

                            {(isVersioned || !isPublished) && (
                                <div className="space-y-3 border-t px-4 py-4">
                                    <FormField
                                        id="change_note"
                                        label={
                                            isVersioned
                                                ? 'What changed'
                                                : 'Note for the first version'
                                        }
                                        hint={
                                            isVersioned
                                                ? 'The version before is kept in its history.'
                                                : 'Publishing makes it version 1.0.'
                                        }
                                        error={
                                            (
                                                form.errors as Partial<
                                                    Record<string, string>
                                                >
                                            ).change_note
                                        }
                                    >
                                        <Textarea
                                            id="change_note"
                                            rows={2}
                                            className="min-h-0"
                                            value={changeNote}
                                            onChange={(event) =>
                                                setChangeNote(
                                                    event.target.value,
                                                )
                                            }
                                            placeholder={
                                                isVersioned
                                                    ? 'Added the steps for the new modem'
                                                    : 'Optional'
                                            }
                                        />
                                    </FormField>
                                    {isVersioned && (
                                        <label className="flex items-start gap-2.5 text-[13px]">
                                            <Checkbox
                                                className="mt-0.5"
                                                checked={isMajor}
                                                onCheckedChange={(checked) =>
                                                    setIsMajor(checked === true)
                                                }
                                            />
                                            <span>
                                                <span className="font-medium">
                                                    Major change
                                                </span>
                                                <span className="block text-xs text-muted-foreground">
                                                    Starts version{' '}
                                                    {nextVersion(
                                                        article.version ??
                                                            '1.0',
                                                        true,
                                                    )}{' '}
                                                    instead of{' '}
                                                    {nextVersion(
                                                        article.version ??
                                                            '1.0',
                                                        false,
                                                    )}
                                                    .
                                                </span>
                                            </span>
                                        </label>
                                    )}
                                </div>
                            )}

                            <p className="border-t px-4 py-2.5 text-xs text-muted-foreground">
                                Press{' '}
                                <kbd className="rounded border bg-muted px-1 font-sans text-[11px]">
                                    {isMac ? '⌘' : 'Ctrl'} S
                                </kbd>{' '}
                                to save.
                            </p>
                        </SidePanel>

                        <SidePanel title="Audience">
                            <div className="px-4 pb-4">
                                <FormField
                                    id="classification"
                                    label="Who can read it"
                                    hint={classification?.description}
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
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                            </div>
                        </SidePanel>

                        <SidePanel title="Details">
                            <div className="space-y-4 px-4 pb-4">
                                <FormField
                                    id="customer_id"
                                    label="About a customer"
                                    error={form.errors.customer_id}
                                >
                                    <NativeSelect
                                        id="customer_id"
                                        value={form.data.customer_id}
                                        onChange={(event) =>
                                            form.setData(
                                                'customer_id',
                                                event.target.value,
                                            )
                                        }
                                    >
                                        <option value="">
                                            No, for the whole team
                                        </option>
                                        {customers.map((customer) => (
                                            <option
                                                key={customer.id}
                                                value={customer.id}
                                            >
                                                {customer.name}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                                <FormField
                                    id="category_id"
                                    label="Category"
                                    error={form.errors.category_id}
                                >
                                    <CategorySelect
                                        id="category_id"
                                        categories={categories}
                                        value={form.data.category_id}
                                        onChange={(event) =>
                                            form.setData(
                                                'category_id',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </FormField>
                                <FormField
                                    id="next_review_on"
                                    label="Next review"
                                    hint="When someone should check it is still right."
                                    error={form.errors.next_review_on}
                                >
                                    <Input
                                        id="next_review_on"
                                        type="date"
                                        value={form.data.next_review_on}
                                        onChange={(event) =>
                                            form.setData(
                                                'next_review_on',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </FormField>
                                <LabelField
                                    selected={form.data.label_ids}
                                    onChange={(ids) =>
                                        form.setData('label_ids', ids)
                                    }
                                    error={form.errors.label_ids}
                                />
                            </div>
                        </SidePanel>
                    </ArticleSidebar>
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

/** One row of the publishing panel: a label on the left, its value on the right. */
function Row({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex min-h-10 items-center justify-between gap-3 px-4 py-2">
            <dt className="shrink-0 text-muted-foreground">{label}</dt>
            <dd className="flex min-w-0 justify-end text-right">{children}</dd>
        </div>
    );
}
