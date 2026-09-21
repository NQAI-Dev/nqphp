<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\DifferentRule;
use PHPUnit\Framework\TestCase;

class DifferentRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new DifferentRule('other', []);
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testPassesWhenOtherFieldMissing(): void
    {
        $rule = new DifferentRule('old_password', []);
        $this->assertTrue($rule->passes('new_secret', 'password'));
    }

    public function testPassesWhenValuesDiffer(): void
    {
        $data = ['old_password' => 'secret123'];
        $rule = new DifferentRule('old_password', $data);
        $this->assertTrue($rule->passes('new_secret456', 'new_password'));
    }

    public function testFailsWhenValuesAreIdentical(): void
    {
        $data = ['old_password' => 'secret123'];
        $rule = new DifferentRule('old_password', $data);
        $this->assertFalse($rule->passes('secret123', 'new_password'));
    }

    public function testStrictTypeComparison(): void
    {
        $data = ['code' => '100'];
        $rule = new DifferentRule('code', $data);
        $this->assertTrue($rule->passes(100, 'input_code'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new DifferentRule('old_password', []);
        $this->assertSame(
            'Значение поля new_password должно отличаться от поля old_password.',
            $rule->message('new_password')
        );

        $custom = new DifferentRule('old_password', [], 'Пароли не должны совпадать');
        $this->assertSame('Пароли не должны совпадать', $custom->message('new_password'));
    }
}
