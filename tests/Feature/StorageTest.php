<?php

namespace Tests\Feature;

use App\Http\Services\StorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StorageTest extends TestCase
{
    protected string $disk;

    public function setUp(): void
    {
        parent::setUp();
        $this->disk = 'public';
        Storage::fake($this->disk);
    }

    #[Test]
    public function it_can_save_a_file_to_storage(): void
    {
        $fileName = 'test-image.png';
        $path = 'cache';
        $fullPath = "{$path}/{$fileName}";
        $fakeFile = UploadedFile::fake()->image($fileName);

        StorageService::saveFile($this->disk, $fullPath, $fakeFile->getContent());

        Storage::disk($this->disk)->assertExists($fullPath);
    }

    #[Test]
    public function it_can_search_for_a_file_in_storage(): void
    {
        $fileName = 'test-image-search.png';
        $path = 'cache';
        $fullPath = "{$path}/{$fileName}";
        $fakeFile = UploadedFile::fake()->image($fileName);

        Storage::disk($this->disk)->put($fullPath, $fakeFile->getContent());

        $exists = StorageService::searchFile($this->disk, $fullPath);

        $this->assertTrue($exists);
    }

    #[Test]
    public function it_can_get_for_a_file_in_storage(): void
    {
        $fileName = 'test-image-get.png';
        $path = 'cache';
        $fullPath = "{$path}/{$fileName}";
        $fakeFile = UploadedFile::fake()->image($fileName);

        Storage::disk($this->disk)->put($fullPath, $fakeFile->getContent());

        $get = StorageService::getFile($this->disk, $fullPath);

        $this->assertEquals($fakeFile->getContent(), $get);
    }


}
