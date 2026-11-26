import { Fragment } from 'react';
import { cn } from '@/lib/utils';

const WEB_ADDRESS = /https?:\/\/[^\s<>"]+/g;

const CLOSING_BRACKETS: Record<string, string> = {
    ')': '(',
    ']': '[',
    '}': '{',
};

/**
 * Where a web address really ends: a full stop, comma or closing bracket
 * after it belongs to the sentence around it, not to the address.
 */
function trimAddress(address: string): string {
    let trimmed = address;

    while (trimmed.length > 0) {
        const last = trimmed[trimmed.length - 1];
        const opening = CLOSING_BRACKETS[last];

        if (opening !== undefined) {
            if (trimmed.includes(opening)) {
                break;
            }
        } else if (!'.,;:!?\'"'.includes(last)) {
            break;
        }

        trimmed = trimmed.slice(0, -1);
    }

    return trimmed;
}

function isWebAddress(address: string): boolean {
    try {
        return ['http:', 'https:'].includes(new URL(address).protocol);
    } catch {
        return false;
    }
}

type Part = { text: string; href?: string };

function splitAtWebAddresses(text: string): Part[] {
    const parts: Part[] = [];
    let position = 0;

    for (const match of text.matchAll(WEB_ADDRESS)) {
        const address = trimAddress(match[0]);

        if (!isWebAddress(address)) {
            continue;
        }

        if (match.index > position) {
            parts.push({ text: text.slice(position, match.index) });
        }

        parts.push({ text: address, href: address });
        position = match.index + address.length;
    }

    if (position < text.length) {
        parts.push({ text: text.slice(position) });
    }

    return parts;
}

/**
 * Plain text someone typed or emailed in, with the web addresses in it
 * turned into links that open in a new tab. Everything else stays as text.
 */
export default function LinkedText({
    text,
    linkClassName,
}: {
    text: string;
    linkClassName?: string;
}) {
    return (
        <>
            {splitAtWebAddresses(text).map((part, index) =>
                part.href ? (
                    <a
                        key={index}
                        href={part.href}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={cn(
                            'wrap-anywhere text-primary underline-offset-2 hover:underline',
                            linkClassName,
                        )}
                    >
                        {part.text}
                    </a>
                ) : (
                    <Fragment key={index}>{part.text}</Fragment>
                ),
            )}
        </>
    );
}
