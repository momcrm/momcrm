import { Form, Head, Link } from '@inertiajs/react';
import FormField from '@/components/form-field';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import StorePortalTicketController from '@/actions/App/Portal/Controllers/StorePortalTicketController';
import { index } from '@/routes/portal/tickets';
import type { Option } from '@/types';

type Props = {
    types: Option[];
    priorities: Option[];
    defaultPriorityId: string | null;
    canAttach: boolean;
    accept: string;
};

/**
 * Raising a ticket in the portal: what it's about, the details, and any
 * screenshots or documents that help.
 */
export default function PortalTicketCreate({
    types,
    priorities,
    defaultPriorityId,
    canAttach,
    accept,
}: Props) {
    return (
        <>
            <Head title="New ticket" />

            <h1 className="text-xl font-semibold">New ticket</h1>

            <Form
                {...StorePortalTicketController.form()}
                className="max-w-2xl space-y-5"
            >
                {({ processing, errors }) => (
                    <>
                        <FormField
                            id="ticket-title"
                            label="What do you need help with?"
                            error={errors.title}
                        >
                            <Input
                                id="ticket-title"
                                name="title"
                                required
                                autoFocus
                                maxLength={255}
                                placeholder="The office printer won't print"
                            />
                        </FormField>

                        <FormField
                            id="ticket-description"
                            label="Details"
                            error={errors.description}
                        >
                            <Textarea
                                id="ticket-description"
                                name="description"
                                rows={7}
                                placeholder="What happened, when it started, and anything you've tried."
                            />
                        </FormField>

                        <div className="grid gap-5 sm:grid-cols-2">
                            {types.length > 0 && (
                                <FormField
                                    id="ticket-type"
                                    label="Type"
                                    error={errors.type_id}
                                >
                                    <NativeSelect
                                        id="ticket-type"
                                        name="type_id"
                                        defaultValue=""
                                    >
                                        <option value="">Not sure</option>
                                        {types.map((type) => (
                                            <option
                                                key={type.value}
                                                value={type.value}
                                            >
                                                {type.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </FormField>
                            )}
                            <FormField
                                id="ticket-priority"
                                label="How urgent is it?"
                                error={errors.priority_id}
                            >
                                <NativeSelect
                                    id="ticket-priority"
                                    name="priority_id"
                                    defaultValue={defaultPriorityId ?? ''}
                                >
                                    {priorities.map((priority) => (
                                        <option
                                            key={priority.value}
                                            value={priority.value}
                                        >
                                            {priority.label}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </FormField>
                        </div>

                        {canAttach && (
                            <FormField
                                id="ticket-files"
                                label="Files"
                                error={errors.files ?? errors['files.0']}
                            >
                                <Input
                                    id="ticket-files"
                                    name="files[]"
                                    type="file"
                                    multiple
                                    accept={accept
                                        .split(',')
                                        .map((extension) => `.${extension}`)
                                        .join(',')}
                                />
                            </FormField>
                        )}

                        <InputError message={errors.ticket} />

                        <div className="flex items-center gap-2">
                            <Button disabled={processing}>
                                {processing && <Spinner />}
                                Raise ticket
                            </Button>
                            <Button variant="ghost" asChild>
                                <Link href={index()}>Cancel</Link>
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}
