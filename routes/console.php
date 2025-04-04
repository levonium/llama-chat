<?php

use Illuminate\Support\Facades\Artisan;
use Cloudstudio\Ollama\Facades\Ollama;
use Illuminate\Console\BufferedConsoleOutput;
use Illuminate\Support\Carbon;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

use function Termwind\{render, terminal};
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\pause;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

function formatMarkdownForTerminal(string $markdown): string
{
    $environment = new Environment();
    $environment->addExtension(new CommonMarkCoreExtension());

    $converter = new MarkdownConverter($environment);
    $html      = $converter->convert($markdown)->getContent();

    // Wrap in a div with small left padding
    $html = "<div class=\"ml-1\">$html</div>";

    // Handle lists
    $html = preg_replace_callback('/<ol(?: start="([0-9]+)")?>(.*?)<\/ol>/s', function ($matches) {
        $start   = isset($matches[1]) ? max(1, (int)$matches[1]) : 1;
        $counter = $start;
        $content = preg_replace_callback('/<li>(.*?)<\/li>/s', function ($liMatches) use (&$counter) {
            $result = "<div>{$counter}. {$liMatches[1]}</div>";
            $counter++;
            return $result;
        }, $matches[2]);
        return "<div class=\"ml-2\">{$content}</div>";
    }, $html);

    $html = preg_replace_callback('/<ul>(.*?)<\/ul>/s', function ($matches) {
        $content = preg_replace('/<li>(.*?)<\/li>/s', '<div>• $1</div>', $matches[1]);
        return "<div class=\"ml-2\">{$content}</div>";
    }, $html);

    // Handle code
    $html = preg_replace('/<code(?: language="([^"]*)"| class="language-([^"]*)")?>(.*?)<\/code>/s', '<span class="bg-slate-700 px-1" data-language="$1$2">$3</span>', $html);
    $html = preg_replace('/<pre><span class="bg-slate-700 px-1" data-language="([^"]*)">(.*?)<\/span><\/pre>/s', '<code class="font-normal" language="$1">$2</code>', $html);

    // Handle blockquotes
    $html = preg_replace('/<blockquote>(.*?)<\/blockquote>/s', '<div class="ml-2 italic">$1</div>', $html);

    // Handle headings
    $html = preg_replace('/<h([1-6])>(.*?)<\/h\1>/s', '<div class="font-bold text-lg">$2</div>', $html);

    return $html;
}

Artisan::command('ollama:ask {--model=llama3.2} {--agent="You are a helpful assistant."}', function (
    string $model,
    string $agent,
) {
    $prompt = textarea(
        label: 'Question ...',
        placeholder: 'E.g. what is the shape of the universe?',
        required: true
    );

    $response = spin(
        message: 'Thinking ...',
        callback: fn () => Ollama::agent($agent)
            ->prompt($prompt)
            ->model($model)
            ->options(['temperature' => 0.8])
            ->stream(false)
            ->ask(),
    );

    if (($response['response'] ?? null) && ($response['total_duration'] ?? null)) {
        $duration = number_format((float) $response['total_duration'] / 1000_000_000, 2);

        render(formatMarkdownForTerminal($response['response']));
        outro("Answer generated in $duration seconds.");
    } else {
        error('Oops, something somewhere must have gone terribly wrong :-|.');
    }
})->purpose('Ask a question to a model.');

Artisan::command('ollama:chat {--model=llama3.2} {--agent="You are a helpful assistant."} {--i} {--f}', function (
    string $model,
    string $agent,
    bool $i = false, // interactive
    bool $f = false, // formatted responses
) {
    $messages = [];

    if ($i) {
        $model = select(
            label: 'Model',
            options: array_column(Ollama::models()['models'], 'name'),
            required: true,
        );

        $agent = textarea(
            label: 'Agent',
            placeholder: 'E.g. you are a helpful assistant.',
            default: 'You are a helpful assistant.',
        );
    }

    while (true) {
        $question = textarea(
            label: 'Message',
            placeholder: 'E.g. what is the shape of the universe?',
            required: true,
        );

        $messages[] = [
            'role' => 'user',
            'content' => $question,
        ];

        $response = spin(
            message: 'Thinking ...',
            callback: fn () => Ollama::agent($agent)
                ->model($model)
                ->stream(true)
                ->chat($messages),
        );

        $output = new BufferedConsoleOutput();
        $output->write('<info>');
        $responses = Ollama::processStream(
            $response->getBody(),
            fn ($data) => $output->write($data['message']['content'])
        );
        $output->write('</info>');
        $output->write("\n");

        $fullResponse = implode('', array_values(array_map(fn ($r) => $r['message']['content'], $responses)));

        if ($f) {
            spin(
                message: 'Formatting ...',
                callback: fn () => sleep(1)
            );

            terminal()->clear();
            render('<div class="pb-4 max-w-72">'.formatMarkdownForTerminal($question).'<hr></div>');
            render(formatMarkdownForTerminal($fullResponse));
        }

        $messages[] = [
            'role' => 'assistant',
            'content' => $fullResponse,
        ];

        pause('Press ENTER to continue.');
    }
})->purpose('Start a chat with a model.');

Artisan::command('ollama:model:list', function () {
    $models = Ollama::models();

    table(
        headers: ['Name', 'Size', 'Last Updated'],
        rows: array_map(
            fn ($model) => [
                $model['name'],
                number_format($model['size'] / (1024 * 1024 * 1024), 2).' GB',
                Carbon::createFromTimeString($model['modified_at'])->toFormattedDateString()
            ],
            $models['models']
        )
    );
});

Artisan::command('ollama:model:show {model?}', function (
    ?string $model = null
) {
    if (!$model) {
        $model = text(
            label: 'Model',
            placeholder: 'llama3',
            required: true,
        );
    }

    $modelData = Ollama::model($model)->show();

    $option = select(
        label: 'choose one',
        options: array_keys($modelData)
    );

    info(is_array($modelData[$option]) ? json_encode($modelData[$option]) : $modelData[$option]);
});
