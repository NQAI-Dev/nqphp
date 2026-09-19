<?php

declare(strict_types=1);

namespace Nqphp\Core\View;

final class AssetCompiler
{
    private string $projectDir;
    private string $publicDir;
    private string $distDir;
    private string $manifestPath;

    public function __construct(string $projectDir)
    {
        $this->projectDir = rtrim($projectDir, '/\\');
        $this->publicDir = $this->projectDir . '/public';
        $this->distDir = $this->publicDir . '/assets/dist';
        $this->manifestPath = $this->distDir . '/manifest.json';
    }

    /**
     * Compile and bundle all feature assets (*.scoped.css and *.js).
     * Writes versioned minified bundles into public/assets/dist/ and generates manifest.json.
     *
     * @return array<string, string> Manifest mapping original paths to compiled URLs
     */
    public function compile(): array
    {
        if (!is_dir($this->distDir)) {
            mkdir($this->distDir, 0775, true);
        }

        $manifest = [];
        $featureDir = $this->projectDir . '/src/Feature';

        if (is_dir($featureDir)) {
            // 1. Compile Scoped CSS
            $cssFiles = glob($featureDir . '/*/*/*.scoped.css') ?: [];
            foreach ($cssFiles as $file) {
                $content = file_get_contents($file) ?: '';
                $relPath = str_replace($this->projectDir . '/', '', $file);

                // Derive route or feature scope key
                $parts = explode('/', $relPath);
                $featureName = strtolower($parts[2] ?? 'common');
                $baseName = pathinfo($file, PATHINFO_FILENAME);
                $routeKey = $featureName . '_' . str_replace('.scoped', '', $baseName);

                $scopeId = ScopedCssCompiler::generateScopeId($routeKey . ':' . $content);
                $compiledCss = ScopedCssCompiler::compile($content, $scopeId);

                $hash = substr(md5($compiledCss), 0, 8);
                $distFileName = sprintf('%s.%s.css', $routeKey, $hash);
                file_put_contents($this->distDir . '/' . $distFileName, $compiledCss);

                $manifest[$relPath] = '/assets/dist/' . $distFileName;
                $manifest[$routeKey] = '/assets/dist/' . $distFileName;
            }

            // 2. Process Feature JavaScript files
            $jsFiles = glob($featureDir . '/*/*/*.js') ?: [];
            foreach ($jsFiles as $file) {
                $content = file_get_contents($file) ?: '';
                $relPath = str_replace($this->projectDir . '/', '', $file);

                $hash = substr(md5($content), 0, 8);
                $baseName = pathinfo($file, PATHINFO_FILENAME);
                $distFileName = sprintf('%s.%s.js', $baseName, $hash);

                // Basic whitespace compression
                $minifiedJs = $this->minifyJs($content);
                file_put_contents($this->distDir . '/' . $distFileName, $minifiedJs);

                $manifest[$relPath] = '/assets/dist/' . $distFileName;
                $manifest[$baseName] = '/assets/dist/' . $distFileName;
            }
        }

        file_put_contents($this->manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $manifest;
    }

    public function getManifest(): array
    {
        if (file_exists($this->manifestPath)) {
            $data = json_decode((string) file_get_contents($this->manifestPath), true);
            if (is_array($data)) {
                return $data;
            }
        }
        return [];
    }

    private function minifyJs(string $js): string
    {
        // Simple and safe whitespace & comment trimming
        $js = preg_replace('!/\*.*?\*/!s', '', $js) ?? $js;
        $js = preg_replace('/\n\s*\n/', "\n", $js) ?? $js;
        return trim($js);
    }
}
