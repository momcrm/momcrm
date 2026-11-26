import { Mail } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { original } from '@/routes/emails';

/**
 * A small button that opens an email as its sender laid it out. The email is
 * shown in a frame that can run nothing and reach nothing in the app, and
 * pictures from the web stay out until asked for.
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
    const [showsPictures, setShowsPictures] = useState(false);

    return (
        <Dialog onOpenChange={(open) => !open && setShowsPictures(false)}>
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
                <iframe
                    key={String(showsPictures)}
                    title="Original email"
                    src={original.url(
                        messageId,
                        showsPictures ? { query: { pictures: 1 } } : undefined,
                    )}
                    sandbox="allow-popups allow-popups-to-escape-sandbox"
                    referrerPolicy="no-referrer"
                    className="h-[65vh] w-full rounded-md border bg-white"
                />
                <div className="flex items-center justify-between gap-3">
                    <p className="text-xs text-muted-foreground">
                        {showsPictures
                            ? 'Pictures from the web are showing.'
                            : 'Pictures from the web are left out: loading one tells the sender the email was opened.'}
                    </p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setShowsPictures(!showsPictures)}
                    >
                        {showsPictures ? 'Hide pictures' : 'Show pictures'}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
