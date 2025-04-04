<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OllamaService;
use Cloudstudio\Ollama\Facades\Ollama;

class OllamaServiceTest extends TestCase
{
    private OllamaService $ollamaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ollamaService = new OllamaService('llama2', 'default');
    }

    public function test_it_can_ask_ollama()
    {
        Ollama::shouldReceive('agent')
            ->with('default')
            ->once()
            ->andReturnSelf();

        Ollama::shouldReceive('prompt')
            ->with('Test prompt')
            ->once()
            ->andReturnSelf();

        Ollama::shouldReceive('model')
            ->with('llama2')
            ->once()
            ->andReturnSelf();

        Ollama::shouldReceive('options')
            ->with(['temperature' => 0.8])
            ->once()
            ->andReturnSelf();

        Ollama::shouldReceive('stream')
            ->with(false)
            ->once()
            ->andReturnSelf();

        Ollama::shouldReceive('ask')
            ->once()
            ->andReturn(['response' => 'Test response']);

        $response = $this->ollamaService->ask('Test prompt');

        $this->assertEquals(['response' => 'Test response'], $response);
    }

    public function test_it_can_chat_with_ollama()
    {
        $messages = [['role' => 'user', 'content' => 'Hello']];

        $mockResponse = new \GuzzleHttp\Psr7\Response(200, [], 'Test stream');

        Ollama::shouldReceive('agent')
            ->with('default')
            ->once()
            ->andReturnSelf();

        Ollama::shouldReceive('model')
            ->with('llama2')
            ->once()
            ->andReturnSelf();

        Ollama::shouldReceive('stream')
            ->with(true)
            ->once()
            ->andReturnSelf();

        Ollama::shouldReceive('chat')
            ->with($messages)
            ->once()
            ->andReturn($mockResponse);

        $response = $this->ollamaService->chat($messages);

        $this->assertEquals([
            'stream' => $mockResponse->getBody(),
            'streamed' => true
        ], $response);
    }

    public function test_it_can_get_models()
    {
        Ollama::shouldReceive('models')
            ->once()
            ->andReturn(['models' => ['llama2', 'mistral']]);

        $models = $this->ollamaService->getModels();

        $this->assertEquals(['llama2', 'mistral'], $models);
    }

    public function test_it_can_get_model_info()
    {
        Ollama::shouldReceive('model')
            ->with('llama2')
            ->once()
            ->andReturnSelf();

        Ollama::shouldReceive('show')
            ->once()
            ->andReturn(['model_info' => 'data']);

        $info = $this->ollamaService->getModelInfo('llama2');

        $this->assertEquals(['model_info' => 'data'], $info);
    }
}
