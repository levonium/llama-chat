<?php

namespace App\Console\Commands;

use App\Console\MarkdownFormatter;
use App\Services\ChatStorage;
use App\Services\ChatUploadManager;
use App\Services\OllamaService;
use Cloudstudio\Ollama\Facades\Ollama;
use Illuminate\Console\BufferedConsoleOutput;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use function Laravel\Prompts\{info, pause, select, spin, textarea, warning};
use function Termwind\{render, terminal};

class ChatCommand extends Command
{
    protected $signature   = 'llama:chat {--model=llama3.2} {--agent="You are a helpful assistant."} {--upload=} {--i} {--f}';
    protected $description = 'Start a chat with a model.';

    private OllamaService $ollama;
    private MarkdownFormatter $formatter;
    private ChatStorage $chatStorage;
    private array $messages  = [];
    private string $fileName = '';
    private bool $formatted  = false;

    public function handle()
    {
        $this->initializeServices();
        $this->handleInteractiveMode();
        $this->startChatLoop();
    }

    private function initializeServices(): void
    {
        $this->ollama      = new OllamaService($this->option('model'), $this->option('agent'));
        $this->formatter   = new MarkdownFormatter();
        $this->chatStorage = new ChatStorage();
        $this->formatted   = $this->option('f');
    }

    private function handleInteractiveMode(): void
    {
        if (!$this->option('i')) {
            return;
        }

        $this->ollama = new OllamaService(
            select(
                label: 'Model',
                options: array_column($this->ollama->getModels(), 'name'),
                required: true,
            ),
            textarea(
                label: 'Agent',
                placeholder: 'E.g. you are a helpful assistant.',
                default: 'You are a helpful assistant.',
            )
        );
    }

    private function startChatLoop(): void
    {
        while (true) {
            $question = textarea(
                label: 'Message',
                placeholder: 'E.g. what is the shape of the universe?',
                required: true,
            );

            $this->messages[] = [
                'role' => 'user',
                'content' => $question,
            ];

            $this->handleFirstMessage($question);
            $this->handleFileUpload();
            $this->processResponse($question);

            pause('Press ENTER to continue.');
        }
    }

    private function handleFirstMessage(string $question): void
    {
        if (count($this->messages) === 1) {
            $this->fileName = $this->chatStorage->saveNewChat(
                $question,
                $this->option('model'),
                $this->option('agent'),
                $this->messages
            );
        }
    }

    private function handleFileUpload(): void
    {
        if (!$this->option('upload')) {
            return;
        }

        $sourcePath = config('app.ollama.chats.cli_uploads').'/'.$this->option('upload');
        $targetPath = config('app.ollama.chats.uploads').'/'.$this->fileName.'/'.$this->option('upload');

        $this->ensureDirectoriesExist();
        $this->processFileUpload($sourcePath, $targetPath);
    }

    private function ensureDirectoriesExist(): void
    {
        foreach ([
            config('app.ollama.chats.cli_uploads'),
            config('app.ollama.chats.uploads').'/'.$this->fileName
        ] as $directory) {
            if (!Storage::disk('local')->exists($directory)) {
                Storage::disk('local')->makeDirectory($directory);
            }
        }
    }

    private function processFileUpload(string $sourcePath, string $targetPath): void
    {
        if (!Storage::disk('local')->exists($sourcePath)) {
            warning('Source file not found: '.$sourcePath);
            return;
        }

        if (Storage::disk('local')->copy($sourcePath, $targetPath)) {
            info('Successfully uploaded file to chat: '.$targetPath);
            Storage::disk('local')->delete($sourcePath);
        } else {
            warning('Failed to upload file to chat. Source: '.$sourcePath.', Target: '.$targetPath);
        }
    }

    private function processResponse(string $question): void
    {
        $uploadManager = new ChatUploadManager($this->fileName);
        $files         = $uploadManager->getUploads();

        $response = spin(
            message: 'Thinking ...',
            callback: fn () => $this->ollama->chat($this->messages, true, $files)
        );

        $fullResponse = $this->processStreamedResponse($response);

        if ($this->formatted) {
            $this->displayFormattedResponse($question, $fullResponse);
        }

        $this->messages[] = [
            'role' => 'assistant',
            'content' => $fullResponse,
        ];

        if ($this->fileName) {
            $this->chatStorage->updateChat($this->fileName, $this->messages);
        }
    }

    private function processStreamedResponse(array $response): string
    {
        if (!($response['streamed'] ?? false)) {
            return $response['response'];
        }

        $output = new BufferedConsoleOutput();
        $output->write('<info>');
        $responses = Ollama::processStream(
            $response['stream'],
            fn ($data) => $output->write($data['message']['content'])
        );
        $output->write('</info>');
        $output->write("\n");

        return implode('', array_values(array_map(
            fn ($r) => $r['message']['content'],
            $responses
        )));
    }

    private function displayFormattedResponse(string $question, string $response): void
    {
        spin(
            message: 'Formatting ...',
            callback: fn () => sleep(1)
        );

        terminal()->clear();
        render('<div class="pb-4 max-w-72">'.$this->formatter->format($question).'<hr></div>');
        render($this->formatter->format($response));
    }
}
