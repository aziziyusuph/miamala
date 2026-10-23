<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Vite;
use RuntimeException;
use Tests\TestCase;

class AssetUrlTest extends TestCase
{
    protected string $originalBuildDirectory;

    protected string $originalHotFile;

    protected string $tempBuildDirectory;

    protected string $tempHotFile;

    protected bool $createdBuildDirectory = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalBuildDirectory = Vite::buildDirectory();
        $this->originalHotFile = Vite::hotFile();

        $this->tempBuildDirectory = 'asset-url-test-build-'.bin2hex(random_bytes(4));
        $this->tempHotFile = storage_path('framework/testing/asset-url-hot-'.bin2hex(random_bytes(4)));

        $manifestDirectory = public_path($this->tempBuildDirectory);

        if (is_dir($manifestDirectory)) {
            throw new RuntimeException('Temporary Vite build directory already exists: '.$manifestDirectory);
        }

        $filesystem = new Filesystem;
        $filesystem->ensureDirectoryExists($manifestDirectory);
        $this->createdBuildDirectory = true;

        $manifestPath = $manifestDirectory.'/manifest.json';
        $filesystem->put(
            $manifestPath,
            json_encode([
                'resources/css/app.css' => [
                    'file' => 'assets/app.css',
                    'src' => 'resources/css/app.css',
                    'isEntry' => true,
                ],
                'resources/js/app.js' => [
                    'file' => 'assets/app.js',
                    'src' => 'resources/js/app.js',
                    'isEntry' => true,
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if (file_exists($this->tempHotFile)) {
            throw new RuntimeException('Temporary Vite hot file already exists: '.$this->tempHotFile);
        }

        Vite::useBuildDirectory($this->tempBuildDirectory);
        Vite::useHotFile($this->tempHotFile);
    }

    protected function tearDown(): void
    {
        try {
            try {
                Vite::useBuildDirectory($this->originalBuildDirectory);
                Vite::useHotFile($this->originalHotFile);
            } finally {
                $filesystem = new Filesystem;

                if ($this->createdBuildDirectory && $filesystem->exists(public_path($this->tempBuildDirectory))) {
                    $filesystem->deleteDirectory(public_path($this->tempBuildDirectory));
                }
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_homepage_uses_configured_asset_origin_for_vite_assets(): void
    {
        config()->set('app.asset_url', 'https://miamala.test');

        $response = $this
            ->withHeader('X-Forwarded-Host', 'evil.example')
            ->get('/');

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString(
            'https://miamala.test/'.$this->tempBuildDirectory.'/assets/app.css',
            $content
        );
        $this->assertStringContainsString(
            'https://miamala.test/'.$this->tempBuildDirectory.'/assets/app.js',
            $content
        );
        $this->assertStringNotContainsString('http://evil.example', $content);
        $this->assertStringNotContainsString('http://miamala.test', $content);
    }
}
