<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class ContentHtml
{
    public static function clean(string $html): string
    {
        $config = (new HtmlSanitizerConfig)->allowElement('p')->allowElement('br')->allowElement('strong')->allowElement('em')->allowElement('s')->allowElement('h2')->allowElement('h3')->allowElement('ul')->allowElement('ol')->allowElement('li')->allowElement('blockquote')->allowElement('a', ['href', 'title'])->allowLinkSchemes(['https', 'http', 'mailto'])->allowRelativeLinks()->forceAttribute('a', 'rel', 'noopener noreferrer');

        return (new HtmlSanitizer($config))->sanitize($html);
    }
}
