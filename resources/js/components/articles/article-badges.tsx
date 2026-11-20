import SoftBadge from '@/components/soft-badge';
import StatusPill from '@/components/status-pill';
import type { ArticleClassification, ArticleStatus, Tone } from '@/types';

export const articleStatusLabels: Record<ArticleStatus, string> = {
    draft: 'Draft',
    published: 'Published',
};

export const articleClassificationLabels: Record<
    ArticleClassification,
    string
> = {
    internal: 'Internal',
    confidential: 'Confidential',
    strictly_confidential: 'Strictly confidential',
};

const classificationTones: Record<ArticleClassification, Tone> = {
    internal: 'neutral',
    confidential: 'warning',
    strictly_confidential: 'destructive',
};

export function ArticleStatusPill({
    status,
    isArchived = false,
}: {
    status: ArticleStatus;
    isArchived?: boolean;
}) {
    if (isArchived) {
        return <StatusPill>Archived</StatusPill>;
    }

    return (
        <StatusPill tone={status === 'published' ? 'success' : 'warning'}>
            {articleStatusLabels[status]}
        </StatusPill>
    );
}

/**
 * Who may read the article. Internal articles, the usual kind, show nothing
 * unless `showInternal` is set.
 */
export function ClassificationBadge({
    classification,
    showInternal = false,
}: {
    classification: ArticleClassification;
    showInternal?: boolean;
}) {
    if (classification === 'internal' && !showInternal) {
        return null;
    }

    return (
        <SoftBadge tone={classificationTones[classification]}>
            {articleClassificationLabels[classification]}
        </SoftBadge>
    );
}
