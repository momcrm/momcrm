import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

type Action = { action: string; method: 'post' };

/**
 * Picks a CSV of customers and uploads it, explaining which columns we read.
 */
export default function CsvUpload({
    action,
    maxRows,
    submitLabel = 'Import a CSV',
}: {
    action: Action;
    maxRows: number;
    submitLabel?: string;
}) {
    return (
        <Form {...action} className="space-y-4">
            {({ processing, errors, progress }) => (
                <>
                    <div className="grid gap-2">
                        <Input
                            type="file"
                            name="file"
                            accept=".csv,text/csv"
                            aria-label="CSV file"
                            required
                        />
                        <InputError message={errors.file} />
                        {progress && (
                            <progress
                                value={progress.percentage}
                                max="100"
                                className="w-full"
                            />
                        )}
                    </div>
                    <p className="text-sm text-muted-foreground">
                        We'll match the columns for you. We need a{' '}
                        <strong>Name</strong> (or Customer, Company) column, and
                        also read Email, Phone, Website, ABN, Contact name,
                        First name, Last name, Contact email and Mobile. Up to{' '}
                        {maxRows.toLocaleString()} rows; rows with the same
                        customer name become one customer.
                    </p>
                    <Button disabled={processing} data-test="import-csv-button">
                        {processing && <Spinner />}
                        {submitLabel}
                    </Button>
                </>
            )}
        </Form>
    );
}
