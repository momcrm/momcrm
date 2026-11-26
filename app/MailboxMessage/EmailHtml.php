<?php

declare(strict_types=1);

namespace App\MailboxMessage;

use Dom\HTMLDocument;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * An email's HTML as a page that is safe to show in a frame. The sender
 * wrote it, so nothing in it is trusted: scripts, frames, forms and anything
 * else that does something are taken out, and links open in a new tab.
 *
 * Cleaning is only half of it. The page must also be served boxed in, with
 * the rules from policy(), so that whatever slips through still cannot run,
 * reach the app, or load anything from elsewhere. The page does run one
 * script, which is ours and only measures it.
 */
final class EmailHtml
{
    /**
     * How the page looks before the email's own styles: a white sheet, as
     * mail programs show it, with pictures no wider than the frame.
     */
    private const string BASE_STYLES = 'html{background:#fff;color:#1f2328}'
        .'body{display:flow-root;margin:12px;font:14px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif;overflow-wrap:anywhere}'
        .'img{max-width:100%;height:auto}';

    /**
     * The one script the page runs, and it is ours. An email laid out wider
     * than the frame is shrunk to fit, though never below 60%, where it
     * scrolls sideways instead. The page that frames it is told how tall the
     * email is, so the frame can be made to fit, and how many pictures from
     * the web it has, so they can be offered. Shown in a frame, the page
     * leaves the scrolling up and down to whatever frames it.
     */
    private const string SCRIPT = '(function(){'
        .'var root=document.documentElement;'
        .'function fit(body){'
        .'body.style.transformOrigin="0 0";body.style.transform="";'
        .'var style=getComputedStyle(body),left=parseFloat(style.marginLeft),right=parseFloat(style.marginRight);'
        .'var natural=root.scrollWidth,room=root.clientWidth;'
        .'if(natural>room+1){body.style.transform="scale("+Math.max((room-left-right)/(natural-left),0.6)+")";}'
        .'}'
        .'function report(){'
        .'var body=document.body;if(!body){return;}'
        .'fit(body);'
        .'var style=getComputedStyle(body);'
        .'var height=body.getBoundingClientRect().height+parseFloat(style.marginTop)+parseFloat(style.marginBottom)+(window.innerHeight-root.clientHeight);'
        .'parent.postMessage({type:"momcrm:email",height:Math.ceil(height),pictures:document.querySelectorAll("img[src^=http]").length},"*");'
        .'}'
        .'if(parent===window){return;}'
        .'root.style.overflowY="hidden";'
        .'document.addEventListener("DOMContentLoaded",function(){report();new ResizeObserver(report).observe(document.body);});'
        .'window.addEventListener("load",report);'
        .'})();';

    /**
     * @param  string  $nonce  the word that lets the page's own script run, which must be the one given to policy() and new for every page
     */
    public static function toPage(string $html, string $nonce): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8">'
            .'<script nonce="'.e($nonce).'">'.self::SCRIPT.'</script>'
            .'<style>'.self::BASE_STYLES.'</style>'
            .self::styles($html)
            .'</head><body>'.self::sanitizer()->sanitize($html).'</body></html>';
    }

    /**
     * What the browser is told about the page, as a Content-Security-Policy.
     * Only the page's own script runs, in a box that cannot reach the app,
     * and nothing is loaded from elsewhere, so reading an email does not
     * tell its sender it was opened. Pictures from the web load only when
     * asked for.
     */
    public static function policy(string $nonce, bool $withPictures = false): string
    {
        return implode('; ', [
            "default-src 'none'",
            "script-src 'nonce-{$nonce}'",
            "style-src 'unsafe-inline'",
            'img-src data:'.($withPictures ? ' https:' : ''),
            "form-action 'none'",
            "frame-ancestors 'self'",
            'sandbox allow-scripts allow-popups allow-popups-to-escape-sandbox',
        ]);
    }

    /**
     * The email's own style sheets, which the cleaner drops with the head.
     * A style sheet is text to the browser until its closing tag, so that is
     * the one thing it must not contain.
     */
    private static function styles(string $html): string
    {
        $styles = '';

        foreach (@HTMLDocument::createFromString($html, 0, 'UTF-8')->getElementsByTagName('style') as $style) {
            $styles .= '<style>'.str_ireplace('</style', '<\/style', $style->textContent ?? '').'</style>';
        }

        return $styles;
    }

    private static function sanitizer(): HtmlSanitizer
    {
        return new HtmlSanitizer(new HtmlSanitizerConfig()
            ->allowStaticElements()
            ->blockElement('form')
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks(false)
            ->allowMediaSchemes(['http', 'https', 'data'])
            ->allowRelativeMedias(false)
            ->forceAttribute('a', 'target', '_blank')
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->withMaxInputLength(-1));
    }
}
