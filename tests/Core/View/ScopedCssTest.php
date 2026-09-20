<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\View;

use Nqphp\Core\View\ScopedCss;
use PHPUnit\Framework\TestCase;

class ScopedCssTest extends TestCase
{
    protected function setUp(): void
    {
        ScopedCss::reset();
    }

    protected function tearDown(): void
    {
        ScopedCss::reset();
    }

    public function testHashGeneratesDeterministicIdentifier(): void
    {
        $h1 = ScopedCss::hash('.btn { color: red; }');
        $h2 = ScopedCss::hash('  .btn { color: red; }  ');
        $h3 = ScopedCss::hash('.btn { color: blue; }');

        $this->assertStringStartsWith('nq-s', $h1);
        $this->assertSame($h1, $h2);
        $this->assertNotSame($h1, $h3);
    }

    public function testCompileWrapsInAtScope(): void
    {
        $compiled = ScopedCss::compile('test-scope', 'h1 { font-size: 2rem; }');
        $this->assertStringContainsString('<style>@scope ([data-nq-scope="test-scope"]) {', $compiled);
        $this->assertStringContainsString('h1 { font-size: 2rem; }', $compiled);
        $this->assertStringEndsWith('}</style>', $compiled);
    }

    public function testCompileEmptyReturnsEmptyString(): void
    {
        $this->assertSame('', ScopedCss::compile('test-scope', '   '));
    }

    public function testCompileOnceDeduplicates(): void
    {
        $css = '.card { padding: 1rem; }';
        $scopeId = 'card-scope';

        $first = ScopedCss::compileOnce($scopeId, $css);
        $this->assertNotEmpty($first);
        $this->assertStringContainsString('@scope ([data-nq-scope="card-scope"])', $first);

        $second = ScopedCss::compileOnce($scopeId, $css);
        $this->assertSame('', $second);

        ScopedCss::reset();
        $afterReset = ScopedCss::compileOnce($scopeId, $css);
        $this->assertSame($first, $afterReset);
    }
}
