import { Banknote } from 'lucide-react';
import { useState } from 'react';
import RecordPaymentDialog from '@/components/payments/record-payment-dialog';
import { Button } from '@/components/ui/button';

/**
 * A small outlined button that records a payment from a customer.
 */
export default function RecordPaymentButton({
    customerId,
}: {
    customerId: string;
}) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <Button variant="outline" size="sm" onClick={() => setOpen(true)}>
                <Banknote />
                Record payment
            </Button>
            <RecordPaymentDialog
                customerId={customerId}
                open={open}
                onOpenChange={setOpen}
            />
        </>
    );
}
