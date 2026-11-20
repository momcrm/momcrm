<?php

declare(strict_types=1);

use App\Article\ArticleBody;

it('keeps what the editor makes', function (): void {
    $html = '<h2>Steps</h2><p>Hold <strong>reset</strong> <em>for</em> <s>5</s> <u>10</u> <code>s</code>.</p>'
        .'<ul><li><p>One</p></li></ul><ol start="3"><li><p>Three</p></li></ol><blockquote><p>Note</p></blockquote>'
        .'<pre><code>ping 1.1.1.1</code></pre><hr><table><tbody><tr><th colspan="2" rowspan="1"><p>H</p></th></tr></tbody></table>';

    expect(ArticleBody::sanitise($html))
        ->toContain('<h2>Steps</h2>', '<strong>reset</strong>', '<ol start="3">', '<pre><code>ping 1.1.1.1</code></pre>', '<th colspan="2" rowspan="1">');
});

it('drops scripts, styles, event handlers and javascript links', function (): void {
    $clean = ArticleBody::sanitise('<p style="color:red" onclick="steal()">Hi <a href="javascript:steal()">x</a></p><script>steal()</script><iframe src="https://evil.test"></iframe><img src="https://evil.test/x.png">');

    expect($clean)->toBe('<p>Hi <a rel="noopener noreferrer nofollow" target="_blank">x</a></p>');
});

it('opens links in a new tab without passing on the page', function (): void {
    expect(ArticleBody::sanitise('<p><a href="https://example.com">docs</a></p>'))
        ->toBe('<p><a href="https://example.com" rel="noopener noreferrer nofollow" target="_blank">docs</a></p>');
});

it('keeps the text of pasted elements the editor does not make', function (): void {
    expect(ArticleBody::sanitise('<h1>Title</h1><div><span>Some <b>bold</b></span></div>'))->toBe('TitleSome bold');
});

it('treats a body with no text as empty', function (): void {
    expect(ArticleBody::sanitise('<p></p><p> </p>'))->toBe('');
});

it('turns the body into plain text, a line per block', function (): void {
    expect(ArticleBody::text('<h2>Steps</h2><p>a&nbsp;&amp;&nbsp;b</p><ul><li><p>one</p></li><li><p>two</p></li></ul><table><tr><td>x</td><td>y</td></tr></table>'))
        ->toBe("Steps\na & b\none\ntwo\nx y");
});
