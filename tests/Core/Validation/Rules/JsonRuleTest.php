<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\JsonRule;
use PHPUnit\Framework\TestCase;

class JsonRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new JsonRule();
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsNonStringOrEmpty(): void
    {
        $rule = new JsonRule();
        $this->assertFalse($rule->passes(null, 'payload'));
        $this->assertFalse($rule->passes([], 'payload'));
        $this->assertFalse($rule->passes(123, 'payload'));
        $this->assertFalse($rule->passes('', 'payload'));
        $this->assertFalse($rule->passes('   ', 'payload'));
    }

    public function testAcceptsValidJson(): void
    {
        $rule = new JsonRule();
        $this->assertTrue($rule->passes('{"key":"value"}', 'payload'));
        $this->assertTrue($rule->passes('[1, 2, 3]', 'payload'));
        $this->assertTrue($rule->passes('"hello"', 'payload'));
        $this->assertTrue($rule->passes('true', 'payload'));
        $this->assertTrue($rule->passes('123', 'payload'));
    }

    public function testRejectsInvalidJson(): void
    {
        $rule = new JsonRule();
        $this->assertFalse($rule->passes('{key: "value"}', 'payload'));
        $this->assertFalse($rule->passes('{"unclosed": ', 'payload'));
        $this->assertFalse($rule->passes('not a json', 'payload'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new JsonRule();
        $this->assertSame('Поле config должно содержать корректную строку JSON.', $rule->message('config'));

        $custom = new JsonRule('Неверный JSON формат');
        $this->assertSame('Неверный JSON формат', $custom->message('config'));
    }
}
