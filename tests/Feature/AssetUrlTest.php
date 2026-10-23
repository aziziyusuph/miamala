<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class AssetUrlTest extends TestCase
{
    protected string $originalBuildDirectory;

    protected string $originalHotFile;

    protected string $tempBuildDirectory;

    protected string $tempHotFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalBuildDirectory = Vite::buildDirectory();
        $this->originalHotFile = Vite::hotFile();

        $this->tempBuildDirectory = 'asset-url-test-build';
        $this->tempHotFile = storage_path('framework/testing/asset-url-hot');

        $manifestDirectory = public_path($this->tempBuildDirectory);
        $filesystem = new Filesystem;

        $filesystem->ensureDirectoryExists($manifestDirectory);
        $filesystem->deleteDirectory($manifestDirectory);
        $filesystem->ensureDirectoryExists($manifestDirectory);

        $filesystem->put(
            $manifestDirectory.'/manifest.json',
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
            unlink($this->tempHotFile);
        }

        Vite::useBuildDirectory($this->tempBuildDirectory);
        Vite::useHotFile($this->tempHotFile);
    }

    protected function tearDown(): void
    {
        Vite::useBuildDirectory($this->originalBuildDirectory);
        Vite::useHotFile($this->originalHotFile);

        $filesystem = new Filesystem;

        if ($filesystem->exists(public_path($this->tempBuildDirectory))) {
            $filesystem->deleteDirectory(public_path($this->tempBuildDirectory));
        }

        if (file_exists($this->tempHotFile)) {
            unlink($this->tempHotFile);
        }

        parent::tearDown();
    }

    public function test_homepage_uses_configured_asset_origin_for_vite_assets(): void
    {
        config()->set('app.asset_url', 'https://miamala.test');

        $response = $this
            ->withHeader('X-Forwarded-Host', 'evil.example')
            ->get('/');

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('https://miamala.test/build/assets/app.css', $content);
        $this->assertStringContainsString('https://miamala.test/build/assets/app.js', $content);
        $this->assertStringNotContainsString('http://evil.example', $content);
        $this->assertStringNotContainsString('http://miamala.test', $content);
    }
}
