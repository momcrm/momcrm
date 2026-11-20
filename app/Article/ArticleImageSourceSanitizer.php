<?php

declare(strict_types=1);

namespace App\Article;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Keeps an image's address only when it is an article image's (see
 * ArticleImages), so a body can't show pictures from anywhere else.
 */
final class ArticleImageSourceSanitizer implements AttributeSanitizerInterface
{
    /**
     * @return list<string>
     */
    public function getSupportedElements(): array
    {
        return ['img'];
    }

    /**
     * @return list<string>
     */
    public function getSupportedAttributes(): array
    {
        return ['src'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        return ArticleImages::isImagePath($value) ? $value : null;
    }
}
