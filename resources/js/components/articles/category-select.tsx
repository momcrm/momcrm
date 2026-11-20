import type { ComponentProps } from 'react';
import NativeSelect from '@/components/native-select';
import type { ArticleCategory } from '@/types';

/**
 * A select of the knowledge base's categories in tree order, each indented
 * under the one it sits in.
 */
export default function CategorySelect({
    categories,
    emptyLabel = 'No category',
    excludeIds,
    ...props
}: Omit<ComponentProps<typeof NativeSelect>, 'children'> & {
    categories: ArticleCategory[];
    emptyLabel?: string;
    /** Categories that can't be chosen, such as one and those under it. */
    excludeIds?: Set<string>;
}) {
    return (
        <NativeSelect {...props}>
            <option value="">{emptyLabel}</option>
            {categories
                .filter((category) => !excludeIds?.has(category.id))
                .map((category) => (
                    <option key={category.id} value={category.id}>
                        {'   '.repeat(category.depth)}
                        {category.name}
                    </option>
                ))}
        </NativeSelect>
    );
}

/**
 * The ids of a category and every category under it.
 */
export function categoryWithDescendants(
    categoryId: string,
    categories: ArticleCategory[],
): Set<string> {
    const found = new Set([categoryId]);
    let frontier = [categoryId];

    while (frontier.length > 0) {
        frontier = categories
            .filter(
                (category) =>
                    category.parent_id !== null &&
                    frontier.includes(category.parent_id) &&
                    !found.has(category.id),
            )
            .map((category) => category.id);
        frontier.forEach((id) => found.add(id));
    }

    return found;
}
