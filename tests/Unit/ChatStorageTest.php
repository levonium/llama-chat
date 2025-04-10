<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ChatStorage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class ChatStorageTest extends TestCase
{
    use RefreshDatabase;

    private ChatStorage $chatStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chatStorage = new ChatStorage();
        Storage::fake('local');
    }

    public function test_save_new_chat_creates_file_with_correct_data(): void
    {
        $title    = 'Test Chat';
        $model    = 'llama3.2';
        $agent    = 'You are a helpful assistant.';
        $messages = [
            ['role' => 'user', 'content' => 'Hello']
        ];

        $fileName = $this->chatStorage->saveNewChat($title, $model, $agent, $messages);

        $this->assertTrue(Storage::disk('local')->exists('chats/'.$fileName));

        $storedData = json_decode(Storage::disk('local')->get('chats/'.$fileName), true);

        $this->assertEquals($title, $storedData['title']);
        $this->assertEquals($model, $storedData['meta']['model']);
        $this->assertEquals($agent, $storedData['meta']['agent']);
        $this->assertEquals($fileName, $storedData['meta']['fileName']);
        $this->assertEquals($messages, $storedData['messages']);
        $this->assertIsInt($storedData['meta']['created_at']);
    }

    public function test_update_chat_updates_existing_file(): void
    {
        // First create a chat
        $fileName = $this->chatStorage->saveNewChat(
            'Test Chat',
            'llama3.2',
            'You are a helpful assistant.',
            [['role' => 'user', 'content' => 'Hello']]
        );

        // Then update it with new messages
        $newMessages = [
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi there!']
        ];

        $this->chatStorage->updateChat($fileName, $newMessages);

        $storedData = json_decode(Storage::disk('local')->get('chats/'.$fileName), true);
        $this->assertEquals($newMessages, $storedData['messages']);
    }

    public function test_load_chat_returns_correct_data(): void
    {
        $messages = [['role' => 'user', 'content' => 'Hello']];
        $fileName = $this->chatStorage->saveNewChat(
            'Test Chat',
            'llama3.2',
            'You are a helpful assistant.',
            $messages
        );

        $loadedData = $this->chatStorage->loadChat($fileName);

        $this->assertEquals('Test Chat', $loadedData['title']);
        $this->assertEquals('llama3.2', $loadedData['meta']['model']);
        $this->assertEquals($messages, $loadedData['messages']);
    }

    public function test_load_recent_chats_returns_correct_number_of_chats(): void
    {
        // Create 15 test chats with different timestamps
        for ($i = 0; $i < 15; $i++) {
            Carbon::setTestNow(now()->addSeconds($i));
            $this->chatStorage->saveNewChat(
                "Test Chat $i",
                'llama3.2',
                'You are a helpful assistant.',
                [['role' => 'user', 'content' => "Message $i"]]
            );
        }
        Carbon::setTestNow();

        // Default limit is 10
        $recentChats = $this->chatStorage->loadRecentChats();
        $this->assertCount(10, $recentChats);

        // Test with custom limit
        $recentChats = $this->chatStorage->loadRecentChats(5);
        $this->assertCount(5, $recentChats);
    }

    public function test_load_recent_chats_returns_chats_in_reverse_order(): void
    {
        // Create 3 test chats with different timestamps
        for ($i = 1; $i <= 3; $i++) {
            Carbon::setTestNow(now()->addSeconds($i));
            $this->chatStorage->saveNewChat(
                "Test Chat $i",
                'llama3.2',
                'You are a helpful assistant.',
                [['role' => 'user', 'content' => "Message $i"]]
            );
        }
        Carbon::setTestNow();

        $recentChats = $this->chatStorage->loadRecentChats(3);

        // Check that chats are in reverse order (most recent first)
        $this->assertEquals('Test Chat 3', $recentChats[0]['title']);
        $this->assertEquals('Test Chat 2', $recentChats[1]['title']);
        $this->assertEquals('Test Chat 1', $recentChats[2]['title']);
    }
}
