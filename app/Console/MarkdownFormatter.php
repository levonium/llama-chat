<?php

namespace App\Console;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownFormatter
{
    public function format(string $markdown): string
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());

        $converter = new MarkdownConverter($environment);
        $html      = $converter->convert($markdown)->getContent();

        $html = "<div class=\"ml-1\">$html</div>";

        $html = $this->formatOrderedLists($html);
        $html = $this->formatUnorderedLists($html);

        $html = $this->formatCodeBlocks($html);

        $html = preg_replace('/<blockquote>(.*?)<\/blockquote>/s', '<div class="ml-2 italic">$1</div>', $html);

        $html = preg_replace('/<h([1-6])>(.*?)<\/h\1>/s', '<div class="font-bold text-lg">$2</div>', $html);

        return $html;
    }

    private function formatOrderedLists(string $html): string
    {
        return preg_replace_callback('/<ol(?: start="([0-9]+)")?>(.*?)<\/ol>/s', function ($matches) {
            $start   = isset($matches[1]) ? max(1, (int)$matches[1]) : 1;
            $counter = $start;
            $content = preg_replace_callback('/<li>(.*?)<\/li>/s', function ($liMatches) use (&$counter) {
                $result = "<div>{$counter}. {$liMatches[1]}</div>";
                $counter++;
                return $result;
            }, $matches[2]);
            return "<div class=\"ml-2 mb-4\">{$content}</div>";
        }, $html);
    }

    private function formatUnorderedLists(string $html): string
    {
        return preg_replace_callback('/<ul>(.*?)<\/ul>/s', function ($matches) {
            $content = preg_replace('/<li>(.*?)<\/li>/s', '<div>• $1</div>', $matches[1]);
            return "<div class=\"ml-2 mb-4\">{$content}</div>";
        }, $html);
    }

    private function formatCodeBlocks(string $html): string
    {
        $html = preg_replace(
            '/<code(?: language="([^"]*)"| class="language-([^"]*)")?>(.*?)<\/code>/s',
            '<span class="bg-slate-700 px-1" data-language="$1$2">$3</span>',
            $html
        );

        return preg_replace(
            '/<pre><span class="bg-slate-700 px-1" data-language="([^"]*)">(.*?)<\/span><\/pre>/s',
            '<code class="font-normal" language="$1">$2</code>',
            $html
        );
    }
}
