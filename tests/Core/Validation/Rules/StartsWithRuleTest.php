<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\StartsWithRule;
use PHPUnit\Framework\TestCase;

class StartsWithRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new StartsWithRule('foo');
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsNonStringOrNumeric(): void
    {
        $rule = new StartsWithRule('prefix');
        $this->assertFalse($rule->passes(null, 'field'));
        $this->assertFalse($rule->passes([], 'field'));
    }

    public function testMatchesSinglePrefix(): void
    {
        $rule = new StartsWithRule('https://');
        $this->assertTrue($rule->passes('https://example.com', 'url'));
        $this->assertFalse($rule->passes('http://example.com', 'url'));
    }

    public function testMatchesArrayOfPrefixes(): void
    {
        $rule = new StartsWithRule(['+7', '8']);
        $this->assertTrue($rule->passes('+79991234567', 'phone'));
        $this->assertTrue($rule->passes('89991234567', 'phone'));
        $this->assertFalse($rule->passes('79991234567', 'phone'));
    }

    public function testHandlesNumericValues(): void
    {
        $rule = new StartsWithRule('2026');
        $this->assertTrue($rule->passes(20260921, 'code'));
        $this->assertFalse($rule->passes(20250921, 'code'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new StartsWithRule(['cat', 'dog']);
        $this->assertSame(
            "Значение поля animal должно начинаться с одного из префиксов: 'cat', 'dog'.",
            $rule->message('animal')
        );

        $custom = new StartsWithRule('test', 'Префикс не совпадает');
        $this->assertSame('Префикс не совпадает', $custom->message('field'));
    }
}
