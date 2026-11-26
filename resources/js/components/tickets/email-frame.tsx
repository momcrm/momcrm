import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';
import { original } from '@/routes/emails';

/** The tallest an email is shown before the rest is folded away. */
const FOLDED_HEIGHT = 480;

/** No email is taller than this; a frame that claims to be is not believed. */
const TALLEST = 30000;

/**
 * An email as its sender laid it out, in a frame as tall as the email. The
 * frame is boxed in: nothing the sender wrote can run in it or reach the
 * app. The page inside says how tall it is and how many pictures from the
 * web it has; those stay out until asked for, because loading one tells
 * the sender the email was opened.
 */
export default function EmailFrame({
    messageId,
    canFold = false,
    className,
}: {
    messageId: string;
    /** Whether a long email is cut short, with a button to show the rest. */
    canFold?: boolean;
    className?: string;
}) {
    const frame = useRef<HTMLIFrameElement>(null);
    const [height, setHeight] = useState<number | null>(null);
    const [pictures, setPictures] = useState(0);
    const [showsPictures, setShowsPictures] = useState(false);
    const [isUnfolded, setIsUnfolded] = useState(false);

    useEffect(() => {
        const onMessage = (event: MessageEvent) => {
            const data = event.data as {
                type?: unknown;
                height?: unknown;
                pictures?: unknown;
            } | null;

            if (
                event.source !== frame.current?.contentWindow ||
                data?.type !== 'momcrm:email'
            ) {
                return;
            }

            const reported = Number(data.height);

            if (Number.isFinite(reported) && reported > 0) {
                setHeight(Math.min(Math.ceil(reported), TALLEST));
            }

            setPictures(Number(data.pictures) > 0 ? Number(data.pictures) : 0);
        };

        window.addEventListener('message', onMessage);

        return () => window.removeEventListener('message', onMessage);
    }, []);

    const isFolded =
        canFold && !isUnfolded && height !== null && height > FOLDED_HEIGHT;

    return (
        <div className={cn('space-y-1.5', className)}>
            <div
                className="relative overflow-hidden rounded-md border bg-white"
                style={isFolded ? { maxHeight: FOLDED_HEIGHT } : undefined}
            >
                <iframe
                    ref={frame}
                    key={String(showsPictures)}
                    title="Original email"
                    src={original.url(
                        messageId,
                        showsPictures ? { query: { pictures: 1 } } : undefined,
                    )}
                    sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox"
                    referrerPolicy="no-referrer"
                    className={cn(
                        'block w-full',
                        height === null && 'animate-pulse',
                    )}
                    style={{ height: height ?? 160 }}
                />
                {isFolded && (
                    <div className="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-linear-to-t from-white to-transparent" />
                )}
            </div>
            {(isFolded ||
                (canFold && isUnfolded) ||
                pictures > 0 ||
                showsPictures) && (
                <p className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                    {canFold && (isFolded || isUnfolded) && (
                        <FrameAction onClick={() => setIsUnfolded(!isUnfolded)}>
                            {isUnfolded ? 'Show less' : 'Show the whole email'}
                        </FrameAction>
                    )}
                    {(pictures > 0 || showsPictures) && (
                        <>
                            <span
                                title="Loading a picture from the web tells the sender the email was opened."
                                className={cn(
                                    canFold &&
                                        (isFolded || isUnfolded) &&
                                        'ml-auto',
                                )}
                            >
                                {showsPictures
                                    ? 'Pictures from the web are showing.'
                                    : 'Pictures from the web are left out.'}
                            </span>
                            <FrameAction
                                onClick={() => setShowsPictures(!showsPictures)}
                            >
                                {showsPictures
                                    ? 'Hide pictures'
                                    : 'Show pictures'}
                            </FrameAction>
                        </>
                    )}
                </p>
            )}
        </div>
    );
}

function FrameAction({
    onClick,
    children,
}: {
    onClick: () => void;
    children: string;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="rounded-sm font-medium text-foreground underline-offset-2 outline-none hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50"
        >
            {children}
        </button>
    );
}
