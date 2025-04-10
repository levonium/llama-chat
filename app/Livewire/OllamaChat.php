<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use App\Services\OllamaService;
use App\Services\ChatStorage;

class OllamaChat extends Component
{
    public $isLoading = false;
    public $messages  = [];
    public $input     = '';

    public $chats     = [];
    public $file      = '';
    public $fileName  = '';
    public $fileTitle = '';

    public $agent           = 'You are a helpful assistant.';
    public $selectedModel   = 'llama3.2:latest';
    public $availableModels = [];

    private OllamaService $ollamaService;
    private ChatStorage $chatStorage;

    public function boot()
    {
        $this->ollamaService   = new OllamaService($this->selectedModel, $this->agent);
        $this->chatStorage     = new ChatStorage();
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
            $this->fileName = $this->chatStorage->saveNewChat(
                $this->input,
                $this->selectedModel,
                $this->agent,
                $this->messages
            );
            $this->fileTitle = str($this->input)->title()->limit(24);
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

            $this->chatStorage->updateChat($this->fileName, $this->messages);
        } catch (\Exception $e) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Sorry, there was an error processing your request.'
            ];

            $this->chatStorage->updateChat($this->fileName, $this->messages);
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadChat($fileName)
    {
        $this->fileName = $fileName;
        $this->dispatch('chat-selected');

        $chatData = $this->chatStorage->loadChat($fileName);

        $this->messages      = $chatData['messages'];
        $this->selectedModel = $chatData['meta']['model'];
        $this->agent         = $chatData['meta']['agent'];
    }

    public function loadChats()
    {
        $this->chats = $this->chatStorage->loadRecentChats();
    }

    public function clearChat()
    {
        $this->messages  = [];
        $this->file      = '';
        $this->fileName  = '';
        $this->fileTitle = '';
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
