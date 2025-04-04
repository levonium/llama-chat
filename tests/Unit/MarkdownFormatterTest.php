<?php

namespace Tests\Unit;

use App\Console\MarkdownFormatter;
use PHPUnit\Framework\TestCase;

class MarkdownFormatterTest extends TestCase
{
    private MarkdownFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new MarkdownFormatter();
    }

    public function test_formats_basic_markdown(): void
    {
        $markdown = '# Hello World';
        $expected = '<div class="ml-1"><div class="font-bold text-lg">Hello World</div>'."\n".'</div>';

        $this->assertEquals($expected, $this->formatter->format($markdown));
    }

    public function test_formats_ordered_lists(): void
    {
        $markdown = "1. First item\n2. Second item";
        $expected = '<div class="ml-1"><div class="ml-2">'."\n".
                   '<div>1. First item</div>'."\n".
                   '<div>2. Second item</div>'."\n".
                   '</div>'."\n".'</div>';

        $this->assertEquals($expected, $this->formatter->format($markdown));
    }

    public function test_formats_unordered_lists(): void
    {
        $markdown = "- First item\n- Second item";
        $expected = '<div class="ml-1"><div class="ml-2">'."\n".
                   '<div>• First item</div>'."\n".
                   '<div>• Second item</div>'."\n".
                   '</div>'."\n".'</div>';

        $this->assertEquals($expected, $this->formatter->format($markdown));
    }

    public function test_formats_code_blocks(): void
    {
        $markdown = "```php\n<?php echo 'Hello';\n```";
        $expected = '<div class="ml-1"><code class="font-normal" language="php">&lt;?php echo \'Hello\';'."\n".
                    '</code>'."\n".'</div>';

        $this->assertEquals($expected, $this->formatter->format($markdown));
    }

    public function test_formats_blockquotes(): void
    {
        $markdown = '> This is a quote';
        $expected = '<div class="ml-1"><div class="ml-2 italic">'."\n".
                   '<p>This is a quote</p>'."\n".
                   '</div>'."\n".'</div>';

        $this->assertEquals($expected, $this->formatter->format($markdown));
    }

    public function test_handles_empty_input(): void
    {
        $this->assertEquals('<div class="ml-1"></div>', $this->formatter->format(''));
    }

    public function test_handles_invalid_markdown(): void
    {
        $invalid  = "This is not\nproper\nmarkdown";
        $expected = '<div class="ml-1"><p>This is not'."\n".
                   'proper'."\n".
                   'markdown</p>'."\n".'</div>';

        $this->assertEquals($expected, $this->formatter->format($invalid));
    }

    public function test_formats_nested_lists(): void
    {
        $markdown = "1. First level\n some text\n  - Second level\n   - Another second\n2. Back to first";
        $expected = '<div class="ml-1">'.
                    '<div class="ml-2">'."\n".
                    '<div>1. First level'."\n".
                    'some text</div>'."\n".
                    '</div>'."\n".
                    '<div class="ml-2">'."\n".
                    '<div>• Second level</div>'."\n".
                    '<div>• Another second</div>'."\n".
                    '</div>'."\n".
                    '<div class="ml-2">'."\n".
                    '<div>2. Back to first</div>'."\n".
                    '</div>'."\n".
                    '</div>';

        $this->assertEquals($expected, $this->formatter->format($markdown));
    }

    public function test_formats_mixed_content(): void
    {
        $markdown = "# Title\n\nParagraph here\n\n- List item\n\n```php\ncode here;\n```";
        $expected = '<div class="ml-1">'.
                    '<div class="font-bold text-lg">Title</div>'."\n".
                    '<p>Paragraph here</p>'."\n".
                    '<div class="ml-2">'."\n".
                    '<div>• List item</div>'."\n".
                    '</div>'."\n".
                    '<code class="font-normal" language="php">code here;'."\n".
                    '</code>'."\n".
                    '</div>';

        $this->assertEquals($expected, $this->formatter->format($markdown));
    }

    public function test_formats_complex_code_blocks(): void
    {
        $markdown = "```php\nif (true) {\n    echo 'nested';\n}\n```";
        $expected = '<div class="ml-1">'.
                    '<code class="font-normal" language="php">if (true) {'."\n".
                    '    echo \'nested\';'."\n".
                    '}'."\n".
                    '</code>'."\n".
                    '</div>';

        $this->assertEquals($expected, $this->formatter->format($markdown));
    }
}
