export type DiffPart = {
    kind: 'same' | 'added' | 'removed';
    text: string;
};

/** Above this many cells, changed lines are shown whole rather than by word. */
const maxWordCells = 250_000;

/**
 * The longest common subsequence of two token lists, walked back into the
 * parts both share and the parts only one has.
 */
function diffTokens(before: string[], after: string[]): DiffPart[] {
    const rows = before.length + 1;
    const columns = after.length + 1;
    const lengths = new Uint32Array(rows * columns);

    for (let i = before.length - 1; i >= 0; i--) {
        for (let j = after.length - 1; j >= 0; j--) {
            lengths[i * columns + j] =
                before[i] === after[j]
                    ? lengths[(i + 1) * columns + j + 1] + 1
                    : Math.max(
                          lengths[(i + 1) * columns + j],
                          lengths[i * columns + j + 1],
                      );
        }
    }

    const parts: DiffPart[] = [];
    const push = (kind: DiffPart['kind'], text: string) => {
        const last = parts[parts.length - 1];

        if (last?.kind === kind) {
            last.text += text;
        } else {
            parts.push({ kind, text });
        }
    };
    let i = 0;
    let j = 0;

    while (i < before.length && j < after.length) {
        if (before[i] === after[j]) {
            push('same', before[i]);
            i++;
            j++;
        } else if (
            lengths[(i + 1) * columns + j] >= lengths[i * columns + j + 1]
        ) {
            push('removed', before[i]);
            i++;
        } else {
            push('added', after[j]);
            j++;
        }
    }

    while (i < before.length) {
        push('removed', before[i++]);
    }

    while (j < after.length) {
        push('added', after[j++]);
    }

    return parts;
}

function words(text: string): string[] {
    return text.match(/\s+|[^\s]+/g) ?? [];
}

/**
 * What changed between two plain texts: lines are matched first, then the
 * words of lines that changed, so long articles stay quick to compare.
 */
export function diffWords(before: string, after: string): DiffPart[] {
    const lineParts = diffTokens(
        before.split(/(?<=\n)/),
        after.split(/(?<=\n)/),
    );
    const parts: DiffPart[] = [];

    for (let index = 0; index < lineParts.length; index++) {
        const part = lineParts[index];
        const next = lineParts[index + 1];

        if (part.kind === 'removed' && next?.kind === 'added') {
            const removedWords = words(part.text);
            const addedWords = words(next.text);

            if (removedWords.length * addedWords.length <= maxWordCells) {
                parts.push(...diffTokens(removedWords, addedWords));
            } else {
                parts.push(part, next);
            }

            index++;
        } else {
            parts.push(part);
        }
    }

    return parts;
}
