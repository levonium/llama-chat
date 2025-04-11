<?php

namespace App\Services;

use Cloudstudio\Ollama\Facades\Ollama;

class OllamaService
{
    public function __construct(
        private readonly string $model,
        private readonly string $agent,
    ) {
    }

    public function ask(string $prompt, bool $stream = false): array
    {
        return Ollama::agent($this->agent)
            ->prompt($prompt)
            ->model($this->model)
            ->options(['temperature' => config('app.ollama.temperature')])
            ->stream($stream)
            ->ask();
    }

    public function chat(array $messages, bool $stream = true, array $files = []): array
    {
        $messages = $this->prepareMessagesWithFiles($messages, $files);

        $response = Ollama::agent($this->agent)
            ->model($this->model)
            ->stream($stream)
            ->chat($messages);

        if ($stream) {
            return [
                'stream' => $response->getBody(),
                'streamed' => true
            ];
        }

        return $response;
    }

    private function prepareMessagesWithFiles(array $messages, array $files): array
    {
        if (empty($files)) {
            return $messages;
        }

        // Add file content to the system message
        $fileContext   = $this->buildFileContext($files);
        $systemMessage = [
            'role' => 'system',
            'content' => $this->agent."\n\nAvailable files and their content:\n".$fileContext
        ];

        // If there's already a system message, merge the content
        if (!empty($messages) && $messages[0]['role'] === 'system') {
            $messages[0]['content'] .= "\n\nAvailable files and their content:\n".$fileContext;
            return $messages;
        }

        // Otherwise, prepend the system message
        array_unshift($messages, $systemMessage);
        return $messages;
    }

    private function buildFileContext(array $files): string
    {
        $context = '';
        foreach ($files as $file) {
            $context .= "\nFile: ".$file['name']."\n";
            if (isset($file['processed_content']['content'])) {
                $context .= "Content:\n".$file['processed_content']['content']."\n";
            } else {
                $context .= "Content: [File content not available]\n";
            }
            $context .= "---\n";
        }
        return $context;
    }

    public function getModels(): array
    {
        return Ollama::models()['models'];
    }

    public function getModelInfo(string $model): array
    {
        return Ollama::model($model)->show();
    }
}
