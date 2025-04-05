<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use App\Services\OllamaService;
use Illuminate\Support\Facades\Storage;

class OllamaChat extends Component
{
    public $messages        = [];
    public $input           = '';
    public $isLoading       = false;
    public $selectedModel   = 'llama3.2:latest';
    public $availableModels = [];
    public $agent           = 'You are a helpful assistant.';

    public $chats        = [];
    public $file         = '';
    public $fileName     = '';
    public $fileTitle    = '';

    private OllamaService $ollamaService;

    public function boot()
    {
        $this->ollamaService   = new OllamaService($this->selectedModel, $this->agent);
        $this->availableModels = $this->ollamaService->getModels();
        $this->loadChats();
    }

    public function sendMessage()
    {
        if (empty($this->input)) {
            return;
        }

        $this->isLoading = true;

        if (empty($this->messages)) {
            $this->fileName  = 'chat-'.now()->timestamp.'.json';
            $this->fileTitle = str($this->input)->title()->limit(24);

            $this->saveChat();
        }

        $this->messages[] = [
            'role' => 'user',
            'content' => $this->input
        ];

        $this->dispatch('message-added');

        $this->input = '';
    }

    #[On('message-added')]
    public function respond()
    {
        try {
            $response = $this->ollamaService->chat(
                messages: $this->messages,
                stream: false
            );

            $this->messages[] = [
                'role' => 'assistant',
                'content' => str($response['message']['content'])->markdownWithHighlight()
            ];

            $this->updateChat();
        } catch (\Exception $e) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Sorry, there was an error processing your request.'
            ];

            $this->updateChat();
        } finally {
            $this->isLoading = false;
        }
    }

    public function saveChat()
    {
        $chatData = [
            'title' => $this->fileTitle,
            'meta' => [
                'model' => $this->selectedModel,
                'agent' => $this->agent,
                'fileName' => $this->fileName,
                'created_at' => now()->timestamp
            ],
            'messages' => $this->messages
        ];

        $this->file = Storage::disk('local')->put('chats/'.$this->fileName, json_encode($chatData));
    }

    public function loadChat($fileName)
    {
        $this->fileName  = $fileName;

        $this->dispatch('chat-selected');

        $chatData = Storage::disk('local')->get('chats/'.$fileName);

        $chatData = json_decode($chatData, true);

        $this->messages = $chatData['messages'];

        $this->selectedModel = $chatData['meta']['model'];
        $this->agent         = $chatData['meta']['agent'];
    }

    public function updateChat()
    {
        $chatData = Storage::disk('local')->get('chats/'.$this->fileName);

        $chatData = json_decode($chatData, true);

        $chatData['messages'] = $this->messages;

        Storage::disk('local')->put('chats/'.$this->fileName, json_encode($chatData));
    }

    public function clearChat()
    {
        $this->messages = [];
    }

    public function loadChats()
    {
        $this->chats = collect(Storage::disk('local')->files('chats'))
            ->map(fn ($chat) => json_decode(Storage::disk('local')->get($chat), true))
            ->reverse()
            ->take(-10);
    }

    public function updatedSelectedModel()
    {
        $this->ollamaService = new OllamaService($this->selectedModel, $this->agent);
    }

    public function updatedAgent()
    {
        $this->ollamaService = new OllamaService($this->selectedModel, $this->agent);
    }

    public function render()
    {
        return view('livewire.ollama-chat');
    }
}
