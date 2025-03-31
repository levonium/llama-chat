<?php

use Illuminate\Support\Facades\Artisan;
use Cloudstudio\Ollama\Facades\Ollama;
use Illuminate\Console\BufferedConsoleOutput;
use Illuminate\Support\Carbon;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\pause;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

// use function Termwind\{render};

Artisan::command('ollama:ask {--model=llama3.2} {--agent="You are a helpful assistant."} {--stream}', function (
    string $model,
    string $agent,
    bool $stream = false,
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
            ->stream($stream)
            ->ask(),
    );

    if (($response['response'] ?? null) && ($response['total_duration'] ?? null)) {
        $duration = number_format((float) $response['total_duration'] / 1000_000_000, 2);

        info($response['response']);
        outro("Answer generated in $duration seconds.");
    } else {
        error('Oops, something somewhere must have gone terribly wrong :-|.');
    }
})->purpose('Ask a question to a model.');

Artisan::command('ollama:chat {--model=llama3.2} {--agent="You are a helpful assistant."} {--i}', function (
    string $model,
    string $agent,
    bool $i = false, // interactive
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

        $messages[] = [
            'role' => 'assistant',
            'content' => implode('', array_values(array_map(fn ($r) => $r['message']['content'], $responses))),
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
