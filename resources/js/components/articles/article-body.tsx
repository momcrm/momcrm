import { cn } from '@/lib/utils';

/**
 * An article's body as the reader sees it. The HTML was sanitised by the
 * server before it was stored (ArticleBody::sanitise), so it is shown as is.
 */
export default function ArticleBody({
    html,
    className,
}: {
    html: string;
    className?: string;
}) {
    return (
        <div
            className={cn('article-body', className)}
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}
