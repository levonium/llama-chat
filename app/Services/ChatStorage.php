<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatStorage
{
    public function saveNewChat(string $title, string $model, string $agent, array $messages): string
    {
        $fileName  = 'chat-'.now()->timestamp.'.json';
        $fileTitle = Str::title(Str::limit($title, 24));

        $chatData = [
            'title' => $fileTitle,
            'meta' => [
                'model' => $model,
                'agent' => $agent,
                'fileName' => $fileName,
                'created_at' => now()->timestamp
            ],
            'messages' => $messages
        ];

        Storage::disk('local')->put('chats/'.$fileName, json_encode($chatData));

        return $fileName;
    }

    public function updateChat(string $fileName, array $messages): void
    {
        $chatData             = Storage::disk('local')->get('chats/'.$fileName);
        $chatData             = json_decode($chatData, true);
        $chatData['messages'] = $messages;
        Storage::disk('local')->put('chats/'.$fileName, json_encode($chatData));
    }

    public function loadChat(string $fileName): array
    {
        $chatData = Storage::disk('local')->get('chats/'.$fileName);
        return json_decode($chatData, true);
    }

    public function loadRecentChats(int $limit = 10): array
    {
        return collect(Storage::disk('local')->files('chats'))
            ->map(fn ($chat) => json_decode(Storage::disk('local')->get($chat), true))
            ->sortByDesc(fn ($chat) => $chat['meta']['created_at'])
            ->take($limit)
            ->values()
            ->toArray();
    }
}
