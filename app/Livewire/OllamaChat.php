<?php

namespace App\Livewire;

use App\Services\ChatStorage;
use App\Services\ChatUploadManager;
use App\Services\OllamaService;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class OllamaChat extends Component
{
    use WithFileUploads;

    public $isLoading    = false;
    public $messages     = [];
    public $input        = '';

    public $chats         = [];
    public $file          = '';
    public $fileName      = '';
    public $fileTitle     = '';

    #[Validate('file|max:10240')]
    public $uploadedFile  = null;
    public $uploads       = [];

    public $agent           = 'You are a helpful assistant.';
    public $selectedModel   = 'llama3.2:latest';
    public $availableModels = [];

    public $selectedChat     = null;
    public $message          = '';

    private OllamaService $ollamaService;
    private ChatStorage $chatStorage;
    private ChatUploadManager $uploadManager;

    public function boot()
    {
        $this->ollamaService   = new OllamaService($this->selectedModel, $this->agent);
        $this->chatStorage     = new ChatStorage();
        $this->availableModels = $this->ollamaService->getModels();
        $this->loadRecentChats();
    }

    public function sendMessage()
    {
        if (empty($this->input)) {
            return;
        }

        $this->isLoading = true;

        if (empty($this->messages) && empty($this->fileName)) {
            $this->fileName = $this->chatStorage->saveNewChat(
                $this->input,
                $this->selectedModel,
                $this->agent,
                $this->messages
            );
            $this->fileTitle     = str($this->input)->title()->limit(24);

            $this->uploadManager = new ChatUploadManager($this->fileName);
            $this->loadUploads();
        } elseif ($this->fileTitle === 'File Upload') {
            $this->chatStorage->updateChatTitle(
                $this->fileName,
                str($this->input)->title()->limit(24)
            );
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
        $this->fileName      = $fileName;
        $this->uploadManager = new ChatUploadManager($this->fileName);
        $this->loadUploads();

        $chatData = $this->chatStorage->loadChat($fileName);

        $this->messages      = $chatData['messages'];
        $this->selectedModel = $chatData['meta']['model'];
        $this->agent         = $chatData['meta']['agent'];
    }

    public function clearChat()
    {
        $this->messages  = [];
        $this->file      = '';
        $this->fileName  = '';
        $this->fileTitle = '';
        $this->uploads   = [];
    }

    public function loadRecentChats()
    {
        $this->chats = $this->chatStorage->loadRecentChats();
    }

    public function loadUploads()
    {
        if ($this->fileName) {
            $this->uploads = $this->uploadManager->getUploads();
        }
    }

    public function updatedFileName()
    {
        if ($this->fileName) {
            $this->uploadManager = new ChatUploadManager($this->fileName);
            $this->loadUploads();
        }
    }

    public function updatedSelectedModel()
    {
        $this->ollamaService = new OllamaService($this->selectedModel, $this->agent);
    }

    public function updatedAgent()
    {
        $this->ollamaService = new OllamaService($this->selectedModel, $this->agent);
    }

    public function updatedUploadedFile()
    {
        if (!$this->fileName) {
            $this->fileName = $this->chatStorage->saveNewChat(
                'File Upload',
                $this->selectedModel,
                $this->agent,
                []
            );
            $this->fileTitle     = 'File Upload';
            $this->uploadManager = new ChatUploadManager($this->fileName);
            $this->loadUploads();
        }

        try {
            $result = $this->uploadManager->upload($this->uploadedFile);
            $this->loadUploads();
            $this->uploadedFile = null;
        } catch (\Exception $e) {
            $this->addError('uploadedFile', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.ollama-chat');
    }
}
