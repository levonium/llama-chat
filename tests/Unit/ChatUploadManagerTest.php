<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ChatUploadManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatUploadManagerTest extends TestCase
{
    use RefreshDatabase;

    private ChatUploadManager $uploadManager;
    private string $chatId = 'test-chat-123';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->uploadManager = new ChatUploadManager($this->chatId);
    }

    public function test_upload_creates_file_with_correct_data(): void
    {
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $result = $this->uploadManager->upload($file);

        $this->assertEquals('test.txt', $result['name']);
        $this->assertTrue(Storage::disk('local')->exists('uploads/'.$this->chatId.'/test.txt'));
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('test.exe', 100, 'application/x-msdownload');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file type or size');

        $this->uploadManager->upload($file);
    }

    public function test_upload_rejects_file_too_large(): void
    {
        $file = UploadedFile::fake()->create('large.txt', 11000, 'text/plain'); // 11MB

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file type or size');

        $this->uploadManager->upload($file);
    }

    public function test_get_file_content_returns_file_contents(): void
    {
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');
        $this->uploadManager->upload($file);

        $result = $this->uploadManager->getFileContent('test.txt');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
    }

    public function test_get_file_content_throws_when_file_not_found(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        $this->uploadManager->getFileContent('nonexistent.txt');
    }

    public function test_delete_file_removes_file(): void
    {
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');
        $this->uploadManager->upload($file);

        $result = $this->uploadManager->deleteFile('test.txt');

        $this->assertTrue($result);
        $this->assertFalse(Storage::disk('local')->exists('uploads/'.$this->chatId.'/test.txt'));
    }

    public function test_delete_file_returns_false_when_file_not_found(): void
    {
        $result = $this->uploadManager->deleteFile('nonexistent.txt');

        $this->assertFalse($result);
    }

    public function test_get_uploads_returns_list_of_files(): void
    {
        $file1 = UploadedFile::fake()->create('test1.txt', 100, 'text/plain');
        $file2 = UploadedFile::fake()->create('test2.txt', 100, 'text/plain');
        $this->uploadManager->upload($file1);
        $this->uploadManager->upload($file2);

        $result = $this->uploadManager->getUploads();

        $this->assertCount(2, $result);
        $this->assertEquals('test1.txt', $result[0]['name']);
        $this->assertEquals('test2.txt', $result[1]['name']);
    }

    public function test_get_uploads_returns_empty_array_when_no_files(): void
    {
        $result = $this->uploadManager->getUploads();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
