<?php

declare(strict_types=1);

namespace Nqphp\Core\View;

use Symfony\Component\HttpFoundation\Response;

/**
 * Route Asset Manager:
 * Manages route-scoped CSS & JS bundles.
 * Compiles and minifies *.scoped.css into public/assets/scoped/
 * Injects link tags into full pages or sends X-NQ-CSS / X-NQ-JS headers for fragment requests.
 */
class RouteAssetManager
{
    private string $publicDir;
    private string $cacheDir;
    private ?array $manifest = null;

    public function __construct(string $projectDir)
    {
        $this->publicDir = rtrim($projectDir, '/') . '/public';
        $this->cacheDir = $this->publicDir . '/assets/scoped';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }

        $manifestFile = $this->publicDir . '/assets/dist/manifest.json';
        if (file_exists($manifestFile)) {
            $parsed = json_decode((string) file_get_contents($manifestFile), true);
            if (is_array($parsed)) {
                $this->manifest = $parsed;
            }
        }
    }

    /**
     * Compile or retrieve cached scoped CSS for a given source CSS file or raw content.
     * Returns array [scopeId, assetUrl]
     *
     * @return array{scopeId: string, assetUrl: string}
     */
    public function registerScopedCss(string $sourceFileOrCss, string $routeKey): array
    {
        $isPath = file_exists($sourceFileOrCss);
        $cssContent = $isPath ? (file_get_contents($sourceFileOrCss) ?: '') : $sourceFileOrCss;
        
        $scopeId = ScopedCssCompiler::generateScopeId($routeKey . ':' . $cssContent);

        // Check if pre-built asset exists in manifest
        if ($this->manifest !== null && isset($this->manifest[$routeKey])) {
            return [
                'scopeId' => $scopeId,
                'assetUrl' => $this->manifest[$routeKey],
            ];
        }

        $fileName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $routeKey) . '.' . substr($scopeId, 2) . '.css';
        $targetPath = $this->cacheDir . '/' . $fileName;
        $assetUrl = '/assets/scoped/' . $fileName;

        if (!file_exists($targetPath) || (defined('DEBUG') && DEBUG)) {
            $compiled = ScopedCssCompiler::compile($cssContent, $scopeId);
            file_put_contents($targetPath, $compiled);
        }

        return [
            'scopeId' => $scopeId,
            'assetUrl' => $assetUrl,
        ];
    }

    /**
     * Attach route assets to a Response (as X-NQ-CSS / X-NQ-JS headers for nq.js fragment swap,
     * or injected link/script tags for full HTML documents).
     */
    public function attachToResponse(Response $response, ?string $cssUrl = null, ?string $jsUrl = null): Response
    {
        if ($cssUrl !== null) {
            $response->headers->set('X-NQ-CSS', $cssUrl);
        }
        if ($jsUrl !== null) {
            $response->headers->set('X-NQ-JS', $jsUrl);
        }

        return $response;
    }
}
