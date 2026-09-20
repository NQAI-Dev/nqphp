<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\View;

use InvalidArgumentException;
use Nqphp\Core\View\ChainTemplateEngine;
use Nqphp\Core\View\TemplateEngineInterface;
use PHPUnit\Framework\TestCase;

class ChainTemplateEngineTest extends TestCase
{
    public function testSupportsReturnsTrueIfAnyEngineSupports(): void
    {
        $engine1 = $this->createMock(TemplateEngineInterface::class);
        $engine1->method('supports')->willReturnCallback(fn (string $tpl) => $tpl === 'index.twig');

        $engine2 = $this->createMock(TemplateEngineInterface::class);
        $engine2->method('supports')->willReturnCallback(fn (string $tpl) => $tpl === 'index.blade');

        $chain = new ChainTemplateEngine([$engine1, $engine2]);

        $this->assertTrue($chain->supports('index.twig'));
        $this->assertTrue($chain->supports('index.blade'));
        $this->assertFalse($chain->supports('other.php'));
    }

    public function testRenderDelegatesToFirstSupportingEngine(): void
    {
        $engine1 = $this->createMock(TemplateEngineInterface::class);
        $engine1->method('supports')->willReturn(false);
        $engine1->expects($this->never())->method('render');

        $engine2 = $this->createMock(TemplateEngineInterface::class);
        $engine2->method('supports')->willReturn(true);
        $engine2->expects($this->once())
            ->method('render')
            ->with('profile.html', ['user' => 'Alex'])
            ->willReturn('<h1>Hello Alex</h1>');

        $chain = new ChainTemplateEngine();
        $chain->addEngine($engine1);
        $chain->addEngine($engine2);

        $this->assertSame('<h1>Hello Alex</h1>', $chain->render('profile.html', ['user' => 'Alex']));
        $this->assertCount(2, $chain->getEngines());
    }

    public function testRenderThrowsExceptionWhenNoEngineSupports(): void
    {
        $chain = new ChainTemplateEngine();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No template engine supports template "unknown.tpl".');

        $chain->render('unknown.tpl');
    }
}
