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

    public function chat(array $messages, bool $stream = true): array
    {
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

    public function getModels(): array
    {
        return Ollama::models()['models'];
    }

    public function getModelInfo(string $model): array
    {
        return Ollama::model($model)->show();
    }
}
