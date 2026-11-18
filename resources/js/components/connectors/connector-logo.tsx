import { Mail } from 'lucide-react';
import { GoogleMark, MicrosoftMark } from '@/components/brand-marks';
import { cn } from '@/lib/utils';

/**
 * A connector type's logo on a small tile: the service's own mark where it
 * has one, and an envelope for a mailbox on any mail server.
 */
export default function ConnectorLogo({
    type,
    className,
}: {
    type: string;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-background shadow-card',
                className,
            )}
        >
            {type === 'microsoft' && <MicrosoftMark />}
            {type === 'google' && <GoogleMark />}
            {type !== 'microsoft' && type !== 'google' && (
                <Mail
                    aria-hidden
                    strokeWidth={1.8}
                    className="size-5 text-muted-foreground"
                />
            )}
        </span>
    );
}
