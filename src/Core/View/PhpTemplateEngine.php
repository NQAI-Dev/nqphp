<?php

declare(strict_types=1);

namespace Nqphp\Core\View;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Native PHP template engine implementing TemplateEngineInterface.
 */
class PhpTemplateEngine implements TemplateEngineInterface
{
    private string $viewsDir;

    public function __construct(string $viewsDir)
    {
        $this->viewsDir = rtrim($viewsDir, '/');
    }

    public function supports(string $template): bool
    {
        return str_ends_with($template, '.php');
    }

    public function render(string $template, array $context = []): string
    {
        if (!$this->supports($template)) {
            throw new InvalidArgumentException(sprintf('Template "%s" is not supported by %s.', $template, self::class));
        }

        $filePath = $this->viewsDir . '/' . ltrim($template, '/');
        if (!file_exists($filePath)) {
            throw new RuntimeException(sprintf('Template file not found: %s', $filePath));
        }

        $renderer = static function (string $__path, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            try {
                include $__path;
            } catch (Throwable $e) {
                ob_end_clean();
                throw $e;
            }
            return (string) ob_get_clean();
        };

        return $renderer($filePath, $context);
    }
}
