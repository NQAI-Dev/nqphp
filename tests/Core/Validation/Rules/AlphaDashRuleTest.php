<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\AlphaDashRule;
use PHPUnit\Framework\TestCase;

class AlphaDashRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new AlphaDashRule();
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsNonStringOrEmpty(): void
    {
        $rule = new AlphaDashRule();
        $this->assertFalse($rule->passes(null, 'slug'));
        $this->assertFalse($rule->passes([], 'slug'));
        $this->assertFalse($rule->passes('', 'slug'));
    }

    public function testAcceptsAlphaNumericWithDashesAndUnderscores(): void
    {
        $rule = new AlphaDashRule();
        $this->assertTrue($rule->passes('valid-slug_123', 'slug'));
        $this->assertTrue($rule->passes('username_test', 'slug'));
        $this->assertTrue($rule->passes('OnlyLetters', 'slug'));
        $this->assertTrue($rule->passes('12345', 'slug'));
        $this->assertTrue($rule->passes(12345, 'slug'));
    }

    public function testRejectsInvalidCharacters(): void
    {
        $rule = new AlphaDashRule();
        $this->assertFalse($rule->passes('invalid slug', 'slug'));
        $this->assertFalse($rule->passes('invalid.slug', 'slug'));
        $this->assertFalse($rule->passes('slug@name', 'slug'));
        $this->assertFalse($rule->passes('slug#1', 'slug'));
        $this->assertFalse($rule->passes('slug/path', 'slug'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new AlphaDashRule();
        $this->assertSame(
            'Поле slug может содержать только буквы, цифры, дефисы и знаки подчеркивания.',
            $rule->message('slug')
        );

        $custom = new AlphaDashRule('Недопустимые символы в slug');
        $this->assertSame('Недопустимые символы в slug', $custom->message('slug'));
    }
}
