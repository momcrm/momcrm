import FormField from '@/components/form-field';
import NativeSelect from '@/components/native-select';
import type { Asset, CustomerOption, Option } from '@/types';
import { Button } from '@/components/ui/button';
import { Form, Link } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type Action = { action: string; method: 'post' | 'put' };

export default function AssetForm({
    action,
    asset,
    statuses,
    locations,
    submitLabel,
    cancelHref,
}: {
    action: Action;
    asset?: Asset;
    statuses: Option[];
    locations: CustomerOption[];
    submitLabel: string;
    cancelHref: string;
}) {
    return (
        <Form {...action} className="max-w-2xl space-y-6">
            {({ processing, errors }) => (
                <>
                    <FormField
                        id="name"
                        label="Name"
                        hint="For example Reception PC or Office firewall."
                        error={errors.name}
                    >
                        <Input
                            id="name"
                            name="name"
                            defaultValue={asset?.name ?? ''}
                            required
                            autoFocus
                        />
                    </FormField>
                    <FormField
                        id="serial_number"
                        label="Serial number"
                        error={errors.serial_number}
                    >
                        <Input
                            id="serial_number"
                            name="serial_number"
                            defaultValue={asset?.serial_number ?? ''}
                        />
                    </FormField>
                    <FormField
                        id="location_id"
                        label="Location"
                        error={errors.location_id}
                    >
                        <NativeSelect
                            id="location_id"
                            name="location_id"
                            defaultValue={asset?.location_id ?? ''}
                        >
                            <option value="">No particular location</option>
                            {locations.map((location) => (
                                <option key={location.id} value={location.id}>
                                    {location.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </FormField>
                    <FormField id="status" label="Status" error={errors.status}>
                        <NativeSelect
                            id="status"
                            name="status"
                            defaultValue={asset?.status ?? 'active'}
                        >
                            {statuses.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </NativeSelect>
                    </FormField>
                    <FormField
                        id="description"
                        label="Description"
                        error={errors.description}
                    >
                        <Textarea
                            id="description"
                            name="description"
                            rows={3}
                            defaultValue={asset?.description ?? ''}
                        />
                    </FormField>

                    <div className="flex items-center gap-2">
                        <Button
                            disabled={processing}
                            data-test="save-asset-button"
                        >
                            {processing && <Spinner />}
                            {submitLabel}
                        </Button>
                        <Button variant="ghost" asChild>
                            <Link href={cancelHref}>Cancel</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
