<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use App\Services\OllamaService;

class OllamaChat extends Component
{
    public $messages          = [];
    public $input             = '';
    public $isLoading         = false;
    public $selectedModel     = 'llama3.2:latest';
    public $availableModels   = [];
    public $agent             = 'You are a helpful assistant';

    private OllamaService $ollamaService;

    public function boot()
    {
        $this->ollamaService   = new OllamaService($this->selectedModel, $this->agent);
        $this->availableModels = $this->ollamaService->getModels();
    }

    public function sendMessage()
    {
        if (empty($this->input)) {
            return;
        }

        $this->isLoading = true;

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
                'content' => $response['message']['content']
            ];
        } catch (\Exception $e) {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Sorry, there was an error processing your request.'
            ];
        } finally {
            $this->isLoading = false;
        }
    }

    public function clearChat()
    {
        $this->messages = [];
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
