<?php

use App\Console\MarkdownFormatter;
use App\Services\ChatStorage;
use App\Services\ChatUploadManager;
use App\Services\OllamaService;
use Cloudstudio\Ollama\Facades\Ollama;
use Illuminate\Console\BufferedConsoleOutput;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\pause;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;
use function Laravel\Prompts\warning;
use function Termwind\{render, terminal};

Artisan::command(
    'llama:ask {--model=llama3.2} {--agent="You are a helpful assistant."}',
    function (
        string $model,
        string $agent,
    ) {
        $prompt = textarea(
            label: 'Question ...',
            placeholder: 'E.g. what is the shape of the universe?',
            required: true
        );

        $ollama    = new OllamaService($model, $agent);
        $formatter = new MarkdownFormatter();

        $response = spin(
            message: 'Thinking ...',
            callback: fn () => $ollama->ask($prompt)
        );

        if (($response['response'] ?? null) && ($response['total_duration'] ?? null)) {
            $duration = number_format((float) $response['total_duration'] / 1000_000_000, 2);

            render($formatter->format($response['response']));
            outro("Answer generated in $duration seconds.");
        } else {
            error('Oops, something somewhere must have gone terribly wrong :-|.');
        }
    }
)->purpose('Ask a question to a model.');

Artisan::command(
    'llama:chat {--model=llama3.2} {--agent="You are a helpful assistant."} {--upload=} {--i} {--f}',
    function (
        string $model,
        string $agent,
        string $upload = '',
        bool $i = false, // interactive
        bool $f = false, // formatted responses
    ) {
        $ollama        = new OllamaService($model, $agent);
        $formatter     = new MarkdownFormatter();
        $messages      = [];
        $fileName      = '';
        $chatStorage   = new ChatStorage();
        $uploadManager = null;
        $uploaded      = false;
        $files         = [];

        if ($i) {
            $model = select(
                label: 'Model',
                options: array_column($ollama->getModels(), 'name'),
                required: true,
            );

            $agent = textarea(
                label: 'Agent',
                placeholder: 'E.g. you are a helpful assistant.',
                default: 'You are a helpful assistant.',
            );

            $upload = text(
                label: 'Upload File',
                placeholder: 'E.g. my-file.txt',
                default: '',
                hint: 'Upload a file to the chat. The file must be in `storage/app/private/cli/` directory.',
            );

            $ollama = new OllamaService($model, $agent);
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

            if (count($messages) === 1) {
                $fileName = $chatStorage->saveNewChat($question, $model, $agent, $messages);

                if ($upload) {
                    $sourcePath = config('app.ollama.chats.cli_uploads').'/'.$upload;
                    $targetPath = config('app.ollama.chats.uploads').'/'.$fileName.'/'.$upload;

                    // Ensure source directory exists
                    if (!Storage::disk('local')->exists(config('app.ollama.chats.cli_uploads'))) {
                        Storage::disk('local')->makeDirectory(config('app.ollama.chats.cli_uploads'));
                    }

                    // Ensure target directory exists
                    if (!Storage::disk('local')->exists(config('app.ollama.chats.uploads').'/'.$fileName)) {
                        Storage::disk('local')->makeDirectory(config('app.ollama.chats.uploads').'/'.$fileName);
                    }

                    // Check if source file exists
                    if (Storage::disk('local')->exists($sourcePath)) {
                        $uploaded = Storage::disk('local')->copy($sourcePath, $targetPath);
                    } else {
                        warning('Source file not found: '.$sourcePath);
                    }

                    if ($uploaded) {
                        info('Successfully uploaded file to chat: '.$targetPath);
                        Storage::disk('local')->delete($sourcePath);
                    } else {
                        warning('Failed to upload file to chat. Source: '.$sourcePath.', Target: '.$targetPath);
                    }
                }
            }

            $uploadManager = new ChatUploadManager($fileName);
            $files         = $uploadManager->getUploads();

            $response = spin(
                message: 'Thinking ...',
                callback: fn () => $ollama->chat($messages, true, $files)
            );

            $fullResponse = '';

            if ($response['streamed'] ?? false) {
                $output = new BufferedConsoleOutput();
                $output->write('<info>');
                $responses = Ollama::processStream(
                    $response['stream'],
                    fn ($data) => $output->write($data['message']['content'])
                );
                $output->write('</info>');
                $output->write("\n");

                $fullResponse = implode('', array_values(array_map(
                    fn ($r) => $r['message']['content'],
                    $responses
                )));
            } else {
                $fullResponse = $response['response'];
            }

            if ($f) {
                spin(
                    message: 'Formatting ...',
                    callback: fn () => sleep(1)
                );

                terminal()->clear();
                render('<div class="pb-4 max-w-72">'.$formatter->format($question).'<hr></div>');
                render($formatter->format($fullResponse));
            }

            $messages[] = [
                'role' => 'assistant',
                'content' => $fullResponse,
            ];

            if ($fileName) {
                $chatStorage->updateChat($fileName, $messages);
            }

            pause('Press ENTER to continue.');
        }
    }
)->purpose('Start a chat with a model.');

Artisan::command(
    'llama:chat:list {--limit=10}',
    function (int $limit) {
        $chatStorage = new ChatStorage();
        $chats       = $chatStorage->loadRecentChats($limit);

        $options = collect($chats)->mapWithKeys(function ($chat) {
            $label = $chat['title'].'('.count($chat['messages']).') 💡 '.$chat['meta']['model'].' 📅 '.Carbon::createFromTimestamp($chat['meta']['created_at'])->format('H:i d M, Y');
            return [$chat['meta']['fileName'] => $label];
        })->toArray();

        $selectedChatId = select(
            label: 'Select a chat',
            options: $options
        );

        $selectedChat = collect($chats)->firstWhere('meta.fileName', $selectedChatId);

        if (!$selectedChat) {
            error('Chat not found');
            return;
        }

        $model    = $selectedChat['meta']['model'];
        $agent    = $selectedChat['meta']['agent'];
        $messages = $selectedChat['messages'];
        $fileName = $selectedChat['meta']['fileName'];
        $ollama   = new OllamaService($model, $agent);

        $uploadManager = new ChatUploadManager($fileName);
        $files         = $uploadManager->getUploads();

        while (true) {
            $question = textarea(
                label: 'Message',
                placeholder: 'Type your message here...',
                required: true,
            );

            $messages[] = [
                'role' => 'user',
                'content' => $question,
            ];

            $response = spin(
                message: 'Thinking ...',
                callback: fn () => $ollama->chat($messages, true, $files)
            );

            $fullResponse = '';

            if ($response['streamed'] ?? false) {
                $output = new BufferedConsoleOutput();
                $output->write('<info>');
                $responses = Ollama::processStream(
                    $response['stream'],
                    fn ($data) => $output->write($data['message']['content'])
                );
                $output->write('</info>');
                $output->write("\n");

                $fullResponse = implode('', array_values(array_map(
                    fn ($r) => $r['message']['content'],
                    $responses
                )));
            } else {
                $fullResponse = $response['response'];
            }

            $messages[] = [
                'role' => 'assistant',
                'content' => $fullResponse,
            ];

            $chatStorage->updateChat($fileName, $messages);

            pause('Press ENTER to continue.');
        }
    }
)->purpose('List previous chats and continue one.');

Artisan::command('llama:model:list', function () {
    $ollama = new OllamaService('llama3.2', 'You are a helpful assistant.');
    $models = $ollama->getModels();

    table(
        headers: ['Name', 'Size', 'Last Updated'],
        rows: array_map(
            fn ($model) => [
                $model['name'],
                number_format($model['size'] / (1024 * 1024 * 1024), 2).' GB',
                Carbon::createFromTimeString($model['modified_at'])->toFormattedDateString()
            ],
            $models
        )
    );
});

Artisan::command('llama:model:show {model?}', function (
    ?string $model = null
) {
    if (!$model) {
        $model = text(
            label: 'Model',
            placeholder: 'llama3',
            required: true,
        );
    }

    $ollama    = new OllamaService($model, 'You are a helpful assistant.');
    $modelData = $ollama->getModelInfo($model);

    $option = select(
        label: 'choose one',
        options: array_keys($modelData)
    );

    info(is_array($modelData[$option]) ? json_encode($modelData[$option]) : $modelData[$option]);
});
