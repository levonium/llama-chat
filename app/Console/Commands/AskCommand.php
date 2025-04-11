<?php

namespace App\Console\Commands;

use App\Console\MarkdownFormatter;
use App\Services\OllamaService;
use Illuminate\Console\Command;

use function Laravel\Prompts\textarea;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\error;
use function Termwind\{render};

class AskCommand extends Command
{
    protected $signature   = 'llama:ask {--model=llama3.2} {--agent="You are a helpful assistant."}';
    protected $description = 'Ask a question to a model.';

    public function handle()
    {
        $prompt = textarea(
            label: 'Question ...',
            placeholder: 'E.g. what is the shape of the universe?',
            required: true
        );

        $response = spin(
            message: 'Thinking ...',
            callback: fn () => (new OllamaService($this->option('model'), $this->option('agent')))->ask($prompt)
        );

        if (($response['response'] ?? null) && ($response['total_duration'] ?? null)) {
            $duration = number_format((float) $response['total_duration'] / 1000_000_000, 2);

            render((new MarkdownFormatter())->format($response['response']));
            outro("Answer generated in $duration seconds.");
        } else {
            error('Oops, something somewhere must have gone terribly wrong :-|.');
        }
    }
}
