<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\View\ScopedCssCompiler;
use PHPUnit\Framework\TestCase;

class ScopedCssCompilerTest extends TestCase
{
    public function testScopeSelector(): void
    {
        $scopeAttr = '[data-nq-s="s-test1"]';

        $this->assertSame(
            '[data-nq-s="s-test1"]',
            ScopedCssCompiler::scopeSelector(':scope', $scopeAttr)
        );

        $this->assertSame(
            'h3[data-nq-s="s-test1"]',
            ScopedCssCompiler::scopeSelector('h3', $scopeAttr)
        );

        $this->assertSame(
            'button.btn[data-nq-s="s-test1"]:hover',
            ScopedCssCompiler::scopeSelector('button.btn:hover', $scopeAttr)
        );

        $this->assertSame(
            'div[data-nq-s="s-test1"] > span[data-nq-s="s-test1"]',
            ScopedCssCompiler::scopeSelector('div > span', $scopeAttr)
        );
    }

    public function testCompileAndMinify(): void
    {
        $rawCss = <<<CSS
        /* General styles */
        :scope {
            background: #000;
            padding: 10px;
        }

        h2, p.intro {
            color: red;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            span.badge {
                display: none;
            }
        }
        CSS;

        $compiled = ScopedCssCompiler::compile($rawCss, 's-abc123');

        $this->assertStringNotContainsString('/* General styles */', $compiled);
        $this->assertStringContainsString('[data-nq-s="s-abc123"]{background:#000;padding:10px}', $compiled);
        $this->assertStringContainsString('h2[data-nq-s="s-abc123"],p.intro[data-nq-s="s-abc123"]{color:red;font-size:14px}', $compiled);
        $this->assertStringContainsString('@media (max-width: 600px){span.badge[data-nq-s="s-abc123"]{display:none}}', $compiled);
    }
}
