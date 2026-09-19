<?php

declare(strict_types=1);

namespace Nqphp\Core\View;

/**
 * Basic native PHP template engine.
 * Renders templates using plain PHP output buffering.
 */
class ViewRenderer
{
    private string $viewsDir;

    public function __construct(string $viewsDir)
    {
        $this->viewsDir = rtrim($viewsDir, '/');
    }

    /**
     * Render a view file with the given data.
     * @param string $view The view path relative to the views directory (e.g. 'hello/index.php').
     * @param array<string, mixed> $data Variables to extract into the view scope.
     */
    public function render(string $view, array $data = []): string
    {
        $viewPath = $this->viewsDir . '/' . ltrim($view, '/');
        if (!file_exists($viewPath)) {
            throw new \RuntimeException(sprintf('View not found: %s', $viewPath));
        }

        // Isolate scope
        $renderFunc = static function(string $__view_path, array $__view_data): string {
            extract($__view_data, EXTR_SKIP);
            ob_start();
            try {
                include $__view_path;
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
            return (string) ob_get_clean();
        };

        return $renderFunc($viewPath, $data);
    }
}
