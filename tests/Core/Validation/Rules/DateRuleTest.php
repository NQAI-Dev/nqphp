<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\DateRule;
use PHPUnit\Framework\TestCase;

class DateRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new DateRule();
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsNonStringOrEmpty(): void
    {
        $rule = new DateRule();
        $this->assertFalse($rule->passes(null, 'birth_date'));
        $this->assertFalse($rule->passes([], 'birth_date'));
        $this->assertFalse($rule->passes('', 'birth_date'));
        $this->assertFalse($rule->passes('   ', 'birth_date'));
    }

    public function testAcceptsDefaultFormat(): void
    {
        $rule = new DateRule();
        $this->assertTrue($rule->passes('2026-09-21', 'date'));
        $this->assertTrue($rule->passes('1999-12-31', 'date'));
        $this->assertFalse($rule->passes('2026-02-30', 'date'));
        $this->assertFalse($rule->passes('21-09-2026', 'date'));
        $this->assertFalse($rule->passes('2026/09/21', 'date'));
    }

    public function testCustomFormat(): void
    {
        $rule = new DateRule(format: 'd.m.Y H:i');
        $this->assertTrue($rule->passes('21.09.2026 14:30', 'published_at'));
        $this->assertFalse($rule->passes('2026-09-21 14:30', 'published_at'));
        $this->assertFalse($rule->passes('21.09.2026', 'published_at'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new DateRule(format: 'Y-m-d');
        $this->assertSame(
            'Поле date должно соответствовать формату даты Y-m-d.',
            $rule->message('date')
        );

        $custom = new DateRule(format: 'd/m/Y', customMessage: 'Неверный формат даты');
        $this->assertSame('Неверный формат даты', $custom->message('date'));
    }
}
