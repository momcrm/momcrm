import { router } from '@inertiajs/react';
import { Send } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { ContactRecipient } from '@/types';

/**
 * A button that emails a document (a receipt, a statement) to some of a
 * customer's contacts, picked in a dialog with the primary contact ticked.
 */
export default function SendToContactsDialog({
    url,
    data = {},
    contacts,
    title,
    description,
    trigger = 'Send',
}: {
    /** Where to post the chosen contact_ids. */
    url: string;
    /** Anything else to post with them. */
    data?: Record<string, string>;
    contacts: ContactRecipient[];
    title: string;
    description: string;
    trigger?: string;
}) {
    const [open, setOpen] = useState(false);
    const [chosen, setChosen] = useState<string[]>([]);
    const [error, setError] = useState<string | undefined>();
    const [processing, setProcessing] = useState(false);

    const start = () => {
        setChosen(
            contacts
                .filter((contact) => contact.is_primary)
                .map((contact) => contact.id),
        );
        setError(undefined);
        setOpen(true);
    };

    const send = () =>
        router.post(
            url,
            { ...data, contact_ids: chosen },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setError(Object.values(errors)[0]),
                onSuccess: () => setOpen(false),
            },
        );

    return (
        <>
            <Button variant="outline" size="sm" onClick={start}>
                <Send />
                {trigger}
            </Button>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                    {contacts.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            None of this customer's contacts has an email
                            address. Add one to a contact first.
                        </p>
                    ) : (
                        <ul className="space-y-2">
                            {contacts.map((contact) => (
                                <li
                                    key={contact.id}
                                    className="flex items-center gap-2"
                                >
                                    <Checkbox
                                        id={`recipient-${contact.id}`}
                                        checked={chosen.includes(contact.id)}
                                        onCheckedChange={(checked) =>
                                            setChosen((current) =>
                                                checked === true
                                                    ? [...current, contact.id]
                                                    : current.filter(
                                                          (id) =>
                                                              id !== contact.id,
                                                      ),
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor={`recipient-${contact.id}`}
                                        className="font-normal"
                                    >
                                        {contact.name}
                                        <span className="text-muted-foreground">
                                            {' '}
                                            · {contact.email}
                                        </span>
                                    </Label>
                                </li>
                            ))}
                        </ul>
                    )}
                    <InputError message={error} />
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" type="button">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            disabled={processing || chosen.length === 0}
                            onClick={send}
                        >
                            {processing && <Spinner />}
                            Send
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
