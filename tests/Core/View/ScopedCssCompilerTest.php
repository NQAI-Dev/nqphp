<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\View;

use Nqphp\Core\View\ScopedCssCompiler;
use PHPUnit\Framework\TestCase;

class ScopedCssCompilerTest extends TestCase
{
    public function testGenerateScopeId(): void
    {
        $id1 = ScopedCssCompiler::generateScopeId('button { color: red; }');
        $id2 = ScopedCssCompiler::generateScopeId('button { color: red; }');
        $id3 = ScopedCssCompiler::generateScopeId('button { color: blue; }');

        $this->assertStringStartsWith('s-', $id1);
        $this->assertSame($id1, $id2);
        $this->assertNotSame($id1, $id3);
    }

    public function testScopeSelector(): void
    {
        $scopeAttr = '[data-nq-s="s-test"]';

        $this->assertSame('[data-nq-s="s-test"] h1', ScopedCssCompiler::scopeSelector('h1', $scopeAttr));
        $this->assertSame('[data-nq-s="s-test"]', ScopedCssCompiler::scopeSelector(':scope', $scopeAttr));
        $this->assertSame('[data-nq-s="s-test"].active', ScopedCssCompiler::scopeSelector(':scope.active', $scopeAttr));
        $this->assertSame('[data-nq-s="s-test"] .card > p', ScopedCssCompiler::scopeSelector('.card > p', $scopeAttr));
    }

    public function testCompileBasicCss(): void
    {
        $css = <<<'CSS'
/* Header styling */
h1 {
    font-size: 24px;
    margin: 0;
}

.btn, :scope.primary {
    color: #fff;
}
CSS;

        $compiled = ScopedCssCompiler::compile($css, 's-123');
        $this->assertStringNotContainsString('/* Header styling */', $compiled);
        $this->assertStringContainsString('[data-nq-s="s-123"] h1{font-size:24px;margin:0}', $compiled);
        $this->assertStringContainsString('[data-nq-s="s-123"] .btn,[data-nq-s="s-123"].primary{color:#fff}', $compiled);
    }

    public function testCompileMediaQueriesAndKeyframes(): void
    {
        $css = <<<'CSS'
@media (max-width: 768px) {
    .container {
        padding: 8px;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
CSS;

        $compiled = ScopedCssCompiler::compile($css, 's-abc');
        $this->assertStringContainsString('@media (max-width: 768px){[data-nq-s="s-abc"] .container{padding:8px}}', $compiled);
        $this->assertStringContainsString('@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}', $compiled);
    }
}
