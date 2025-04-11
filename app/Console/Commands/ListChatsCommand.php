<?php

namespace App\Console\Commands;

use App\Services\ChatStorage;
use App\Services\ChatUploadManager;
use App\Services\OllamaService;
use Cloudstudio\Ollama\Facades\Ollama;
use Illuminate\Console\BufferedConsoleOutput;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\textarea;

class ListChatsCommand extends Command
{
    protected $signature   = 'llama:chat:list {--limit=10}';
    protected $description = 'List and continue a previous chat.';

    public function handle()
    {
        $chatStorage = new ChatStorage();
        $chats       = $chatStorage->loadRecentChats($this->option('limit'));

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
            error('Chat not found.');
            return;
        }

        $this->continueChat($selectedChat);
    }

    private function continueChat(array $chat): void
    {
        $model    = $chat['meta']['model'];
        $agent    = $chat['meta']['agent'];
        $messages = $chat['messages'];
        $fileName = $chat['meta']['fileName'];
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

            $fullResponse = $this->processStreamedResponse($response);

            $messages[] = [
                'role' => 'assistant',
                'content' => $fullResponse,
            ];

            $chatStorage = new ChatStorage();
            $chatStorage->updateChat($fileName, $messages);
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
}
