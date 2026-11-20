<?php

declare(strict_types=1);

namespace App\Article;

use App\Article\Properties\Body;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * An article's body as the editor sends it: HTML, cut down to the elements
 * the editor can make before it is stored, so the page can show it as it
 * is. Anything else (scripts, styles, event handlers, javascript: links,
 * iframes) is dropped.
 */
final class ArticleBody
{
    /**
     * Elements the editor makes, each with the attributes it may keep.
     *
     * @var array<string, list<string>>
     */
    private const array ALLOWED = [
        'p' => [],
        'br' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'strong' => [],
        'em' => [],
        's' => [],
        'u' => [],
        'code' => [],
        'pre' => [],
        'blockquote' => [],
        'ul' => [],
        'ol' => ['start'],
        'li' => [],
        'hr' => [],
        'a' => ['href'],
        'table' => [],
        'colgroup' => [],
        'col' => [],
        'thead' => [],
        'tbody' => [],
        'tr' => [],
        'th' => ['colspan', 'rowspan'],
        'td' => ['colspan', 'rowspan'],
    ];

    /**
     * Elements the editor doesn't make, but whose text is kept when they
     * come in anyway (pasted from elsewhere, say). Anything not listed here
     * or above is dropped with its contents.
     *
     * @var list<string>
     */
    private const array UNWRAPPED = [
        'h1', 'h5', 'h6', 'b', 'i', 'span', 'div', 'section', 'article', 'header', 'footer', 'main',
        'font', 'mark', 'small', 'sub', 'sup', 'del', 'ins', 'kbd', 'samp', 'abbr', 'cite', 'q',
        'figure', 'figcaption', 'label', 'center', 'dl', 'dt', 'dd',
    ];

    public static function sanitise(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $sanitised = trim(self::sanitizer()->sanitize($html));

        return self::text($sanitised) === '' ? '' : $sanitised;
    }

    /**
     * The body as plain text, a line per paragraph, heading or list item,
     * for searching.
     */
    public static function text(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $spaced = (string) preg_replace(
            ['#</(p|h[1-6]|li|blockquote|pre|tr)>|<br\s*/?>|<hr\s*/?>#i', '#</(th|td)>#i'],
            ["$0\n", '$0 '],
            $html,
        );
        $text = html_entity_decode(strip_tags($spaced), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lines = array_map(
            fn (string $line): string => trim((string) preg_replace('/[ \t\x{00A0}]+/u', ' ', $line)),
            explode("\n", $text),
        );

        return implode("\n", array_filter($lines, fn (string $line): bool => $line !== ''));
    }

    private static function sanitizer(): HtmlSanitizer
    {
        $config = new HtmlSanitizerConfig()
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks(false)
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->forceAttribute('a', 'target', '_blank')
            ->withMaxInputLength(Body::MAX_LENGTH);

        foreach (self::ALLOWED as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }

        foreach (self::UNWRAPPED as $element) {
            $config = $config->blockElement($element);
        }

        return new HtmlSanitizer($config);
    }
}
