<?php

namespace Tests\Feature;

use App\Livewire\OllamaChat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OllamaChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_can_upload_file_and_start_chat()
    {
        $file = UploadedFile::fake()->createWithContent('test.txt', 'Test content');

        $component = Livewire::test(OllamaChat::class)
            ->set('uploadedFile', $file)
            ->assertHasNoErrors()
            ->assertSet('fileTitle', 'test');

        $this->assertTrue(Storage::disk('local')->exists('uploads/'.$component->get('fileName').'/test.txt'));
    }

    public function test_can_send_message_with_file_context()
    {
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $component = Livewire::test(OllamaChat::class)
            ->set('uploadedFile', $file)
            ->set('input', 'What is in the file?')
            ->call('sendMessage');

        $this->assertNotEmpty($component->get('messages'));
        $this->assertEquals('user', $component->get('messages')[0]['role']);
        $this->assertEquals('What is in the file?', $component->get('messages')[0]['content']);
    }

    public function test_validates_file_size()
    {
        $file = UploadedFile::fake()->create('large.txt', 11000, 'text/plain'); // 11MB

        Livewire::test(OllamaChat::class)
            ->set('uploadedFile', $file)
            ->assertHasErrors(['uploadedFile']);
    }

    public function test_can_clear_chat_and_uploads()
    {
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $component = Livewire::test(OllamaChat::class)
            ->set('uploadedFile', $file)
            ->set('input', 'Test message')
            ->call('sendMessage')
            ->call('clearChat');

        $component->assertSet('messages', [])
            ->assertSet('file', '')
            ->assertSet('fileName', '')
            ->assertSet('fileTitle', '')
            ->assertSet('uploads', []);
    }

    public function test_validates_file_type()
    {
        $file = UploadedFile::fake()->create('test.exe', 100, 'application/x-msdownload');

        Livewire::test(OllamaChat::class)
            ->set('uploadedFile', $file)
            ->assertHasErrors(['uploadedFile']);
    }
}
