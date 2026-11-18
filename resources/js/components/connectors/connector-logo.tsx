import { Plug } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * A connector type's logo on a small tile. The type supplies the image; one
 * without a logo, or that is no longer installed, gets a plain plug.
 */
export default function ConnectorLogo({
    logo,
    className,
}: {
    /** The logo as an image address, from the connector type. */
    logo: string | null | undefined;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-background shadow-card',
                className,
            )}
        >
            {logo ? (
                <img src={logo} alt="" className="size-5" />
            ) : (
                <Plug
                    aria-hidden
                    strokeWidth={1.8}
                    className="size-5 text-muted-foreground"
                />
            )}
        </span>
    );
}
