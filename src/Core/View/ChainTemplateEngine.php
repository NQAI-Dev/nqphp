<?php

declare(strict_types=1);

namespace Nqphp\Core\View;

use InvalidArgumentException;

/**
 * Composite template engine delegating rendering to the first supporting engine.
 */
class ChainTemplateEngine implements TemplateEngineInterface
{
    /** @var TemplateEngineInterface[] */
    private array $engines = [];

    /**
     * @param TemplateEngineInterface[] $engines
     */
    public function __construct(array $engines = [])
    {
        foreach ($engines as $engine) {
            $this->addEngine($engine);
        }
    }

    public function addEngine(TemplateEngineInterface $engine): self
    {
        $this->engines[] = $engine;
        return $this;
    }

    /**
     * @return TemplateEngineInterface[]
     */
    public function getEngines(): array
    {
        return $this->engines;
    }

    public function supports(string $template): bool
    {
        foreach ($this->engines as $engine) {
            if ($engine->supports($template)) {
                return true;
            }
        }
        return false;
    }

    public function render(string $template, array $context = []): string
    {
        foreach ($this->engines as $engine) {
            if ($engine->supports($template)) {
                return $engine->render($template, $context);
            }
        }

        throw new InvalidArgumentException(sprintf('No template engine supports template "%s".', $template));
    }
}
