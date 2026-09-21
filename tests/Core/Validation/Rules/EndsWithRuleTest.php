<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\EndsWithRule;
use PHPUnit\Framework\TestCase;

class EndsWithRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new EndsWithRule('.jpg');
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsNonStringOrNumeric(): void
    {
        $rule = new EndsWithRule('.png');
        $this->assertFalse($rule->passes(null, 'filename'));
        $this->assertFalse($rule->passes([], 'filename'));
    }

    public function testMatchesSingleSuffix(): void
    {
        $rule = new EndsWithRule('.pdf');
        $this->assertTrue($rule->passes('document.pdf', 'file'));
        $this->assertFalse($rule->passes('document.txt', 'file'));
    }

    public function testMatchesArrayOfSuffixes(): void
    {
        $rule = new EndsWithRule(['.jpg', '.jpeg', '.png']);
        $this->assertTrue($rule->passes('avatar.png', 'avatar'));
        $this->assertTrue($rule->passes('photo.jpg', 'avatar'));
        $this->assertTrue($rule->passes('image.jpeg', 'avatar'));
        $this->assertFalse($rule->passes('archive.zip', 'avatar'));
    }

    public function testHandlesNumericValues(): void
    {
        $rule = new EndsWithRule('00');
        $this->assertTrue($rule->passes(5000, 'amount'));
        $this->assertFalse($rule->passes(5001, 'amount'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new EndsWithRule(['.json', '.xml']);
        $this->assertSame(
            "Значение поля format должно заканчиваться одним из суффиксов: '.json', '.xml'.",
            $rule->message('format')
        );

        $custom = new EndsWithRule('.log', 'Файл должен иметь расширение .log');
        $this->assertSame('Файл должен иметь расширение .log', $custom->message('log_file'));
    }
}
