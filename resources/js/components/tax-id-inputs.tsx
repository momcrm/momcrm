import type { ComponentProps } from 'react';
import { useId } from 'react';
import { Input } from '@/components/ui/input';

/** What tax IDs are called in the countries the app bills in. */
export const TAX_ID_LABELS = [
    'ABN',
    'GST No.',
    'NZBN',
    'VAT',
    'UEN',
    'TIN',
    'BN',
    'BRN',
    'EIN',
];

/**
 * A tax ID with what it is called beside it (ABN, VAT, GST No., ...),
 * suggested from the usual names but free to type.
 */
export default function TaxIdInputs({
    labelInput,
    idInput,
}: {
    /** The input for what the tax ID is called. */
    labelInput: ComponentProps<'input'>;
    /** The input for the tax ID itself. */
    idInput: ComponentProps<'input'>;
}) {
    const listId = useId();

    return (
        <div className="flex gap-2">
            <Input
                aria-label="What the tax ID is called"
                list={listId}
                placeholder="ABN"
                className="w-28 shrink-0"
                {...labelInput}
            />
            <datalist id={listId}>
                {TAX_ID_LABELS.map((label) => (
                    <option key={label} value={label} />
                ))}
            </datalist>
            <Input placeholder="12 345 678 901" {...idInput} />
        </div>
    );
}

/** A tax ID as printed, e.g. "ABN 12 345 678 901", or null without one. */
export function taxIdLine(holder: {
    tax_id: string | null;
    tax_id_label: string | null;
}): string | null {
    return holder.tax_id
        ? `${holder.tax_id_label ?? 'Tax ID'} ${holder.tax_id}`
        : null;
}
