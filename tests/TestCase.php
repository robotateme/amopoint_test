<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    private bool $hadViteManifest = false;

    private ?string $previousViteManifest = null;

    protected function setUp(): void
    {
        parent::setUp();

        $manifestPath = public_path('build/manifest.json');
        $this->hadViteManifest = File::exists($manifestPath);
        $this->previousViteManifest = $this->hadViteManifest ? File::get($manifestPath) : null;

        File::ensureDirectoryExists(dirname($manifestPath));
        File::put($manifestPath, json_encode([
            'resources/css/app.css' => [
                'file' => 'assets/app-test.css',
                'src' => 'resources/css/app.css',
                'isEntry' => true,
            ],
            'resources/js/app.js' => [
                'file' => 'assets/app-test.js',
                'src' => 'resources/js/app.js',
                'isEntry' => true,
            ],
        ], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        $manifestPath = public_path('build/manifest.json');

        if ($this->hadViteManifest) {
            File::put($manifestPath, (string) $this->previousViteManifest);
        } else {
            File::delete($manifestPath);
        }

        parent::tearDown();
    }
}
