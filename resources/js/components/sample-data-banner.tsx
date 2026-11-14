import { Form, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import RemoveSampleDataController from '@/actions/App/Onboarding/Controllers/RemoveSampleDataController';

/**
 * Reminds the team they're looking at the sample customers from onboarding,
 * with a one-click way to remove them.
 */
export default function SampleDataBanner() {
    const { hasSampleData } = usePage().props;

    if (!hasSampleData) {
        return null;
    }

    return (
        <div className="mx-4 mt-2 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-dashed bg-muted/50 px-4 py-2 text-sm md:mx-6">
            <span>You're looking at sample data.</span>
            <Form {...RemoveSampleDataController.form()}>
                {({ processing }) => (
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={processing}
                        data-test="remove-sample-data-button"
                    >
                        Remove sample data
                    </Button>
                )}
            </Form>
        </div>
    );
}
