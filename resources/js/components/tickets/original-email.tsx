import { Mail } from 'lucide-react';
import EmailFrame from '@/components/tickets/email-frame';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

/**
 * A small button that opens an email as its sender laid it out, for an
 * email that is not shown in place, such as a reply among the comments.
 */
export default function OriginalEmail({
    messageId,
    label = 'Original email',
    className,
}: {
    messageId: string;
    label?: string;
    className?: string;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <button
                    type="button"
                    title="View the email as it was sent"
                    className={cn(
                        'inline-flex items-center gap-1 rounded-sm text-xs font-normal tracking-normal text-subtle-foreground normal-case outline-none hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50',
                        className,
                    )}
                >
                    <Mail className="size-3.5" />
                    {label}
                </button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-3xl">
                <DialogTitle>Original email</DialogTitle>
                <DialogDescription>
                    The email as it was sent. Links open in a new tab.
                </DialogDescription>
                <div className="max-h-[70vh] overflow-y-auto">
                    <EmailFrame messageId={messageId} />
                </div>
            </DialogContent>
        </Dialog>
    );
}
