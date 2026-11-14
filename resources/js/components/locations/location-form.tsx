import FormField from '@/components/form-field';
import type { Location } from '@/types';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Form, Link } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Action = { action: string; method: 'post' | 'put' };

export default function LocationForm({
    action,
    location,
    submitLabel,
    cancelHref,
}: {
    action: Action;
    location?: Location;
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
                        hint="For example Head office or Warehouse."
                        error={errors.name}
                    >
                        <Input
                            id="name"
                            name="name"
                            defaultValue={location?.name ?? ''}
                            required
                            autoFocus
                        />
                    </FormField>
                    <FormField
                        id="street_one"
                        label="Street address"
                        error={errors.street_one}
                    >
                        <Input
                            id="street_one"
                            name="street_one"
                            defaultValue={location?.street_one ?? ''}
                        />
                    </FormField>
                    <FormField
                        id="street_two"
                        label="Street address line 2"
                        error={errors.street_two}
                    >
                        <Input
                            id="street_two"
                            name="street_two"
                            defaultValue={location?.street_two ?? ''}
                        />
                    </FormField>
                    <FormField id="suburb" label="Suburb" error={errors.suburb}>
                        <Input
                            id="suburb"
                            name="suburb"
                            defaultValue={location?.suburb ?? ''}
                        />
                    </FormField>
                    <FormField id="state" label="State" error={errors.state}>
                        <Input
                            id="state"
                            name="state"
                            defaultValue={location?.state ?? ''}
                        />
                    </FormField>
                    <FormField
                        id="postcode"
                        label="Postcode"
                        error={errors.postcode}
                    >
                        <Input
                            id="postcode"
                            name="postcode"
                            defaultValue={location?.postcode ?? ''}
                        />
                    </FormField>
                    <FormField
                        id="country"
                        label="Country"
                        error={errors.country}
                    >
                        <Input
                            id="country"
                            name="country"
                            defaultValue={location?.country ?? ''}
                        />
                    </FormField>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="is_primary"
                            name="is_primary"
                            value="1"
                            defaultChecked={location?.is_primary}
                        />
                        <Label htmlFor="is_primary">
                            Main location for this customer
                        </Label>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            disabled={processing}
                            data-test="save-location-button"
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
