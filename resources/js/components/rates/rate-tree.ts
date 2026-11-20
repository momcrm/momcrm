import type { Rate } from '@/types';

/**
 * The ids of every rate under one, however deep.
 */
export function rateDescendantIds(rateId: string, rates: Rate[]): Set<string> {
    const found = new Set<string>();
    let frontier = [rateId];

    while (frontier.length > 0) {
        const next = rates
            .filter(
                (rate) =>
                    rate.parent_id !== null &&
                    frontier.includes(rate.parent_id) &&
                    !found.has(rate.id),
            )
            .map((rate) => rate.id);

        next.forEach((id) => found.add(id));
        frontier = next;
    }

    return found;
}

/**
 * How many of the given rates a rate sits under, so a list that leaves some
 * out (archived ones, say) can still indent what it shows.
 */
export function depthAmong(rate: Rate, rates: Rate[]): number {
    const byId = new Map(rates.map((other) => [other.id, other]));
    const seen = new Set<string>();
    let depth = 0;
    let parentId = rate.parent_id;

    while (parentId && !seen.has(parentId)) {
        seen.add(parentId);
        const parent = byId.get(parentId);

        if (!parent) {
            break;
        }

        depth++;
        parentId = parent.parent_id;
    }

    return depth;
}
