/** How many words an article's HTML body holds. */
export function countWords(html: string): number {
    return html
        .replace(/<[^>]+>/g, ' ')
        .split(/\s+/u)
        .filter(Boolean).length;
}

/** Minutes to read a body at about 220 words a minute, at least one. */
export function readingMinutes(html: string): number {
    return Math.max(1, Math.round(countWords(html) / 220));
}
