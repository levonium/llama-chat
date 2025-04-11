<?php

namespace App\Console\Commands;

use App\Services\OllamaService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\info;

class ModelCommand extends Command
{
    protected $signature   = 'llama:model';
    protected $description = 'List models and show information about them.';

    protected OllamaService $ollama;

    public function __construct(
    ) {
        parent::__construct();

        $this->ollama = new OllamaService('', '');
    }

    public function handle()
    {
        $action = select(
            label: 'Action',
            options: ['List models', 'Show model info'],
            required: true,
        );

        if ($action === 'List models') {
            $this->listModels();
        }

        if ($action === 'Show model info') {
            $this->showModelInfo();
        }
    }

    private function listModels()
    {
        $models = $this->ollama->getModels();

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
    }

    private function showModelInfo()
    {
        $models = $this->ollama->getModels();

        $model = select(
            label: 'Model',
            options: array_column($models, 'name'),
        );

        $modelData = $this->ollama->getModelInfo($model);

        $option = select(
            label: 'Choose one',
            options: array_keys($modelData)
        );

        info(
            is_array($modelData[$option])
            ? json_encode($modelData[$option])
            : $modelData[$option]
        );
    }
}
