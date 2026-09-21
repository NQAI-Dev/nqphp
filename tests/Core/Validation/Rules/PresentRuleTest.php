<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\PresentRule;
use PHPUnit\Framework\TestCase;

class PresentRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new PresentRule([]);
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testPassesWhenKeyExistsEvenIfNullOrEmpty(): void
    {
        $data = [
            'name' => 'Alice',
            'middle_name' => null,
            'notes' => '',
            'tags' => [],
        ];
        $rule = new PresentRule($data);

        $this->assertTrue($rule->passes($data['name'], 'name'));
        $this->assertTrue($rule->passes($data['middle_name'], 'middle_name'));
        $this->assertTrue($rule->passes($data['notes'], 'notes'));
        $this->assertTrue($rule->passes($data['tags'], 'tags'));
    }

    public function testFailsWhenKeyDoesNotExist(): void
    {
        $data = ['name' => 'Alice'];
        $rule = new PresentRule($data);

        $this->assertFalse($rule->passes(null, 'email'));
        $this->assertFalse($rule->passes(null, 'password'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new PresentRule([]);
        $this->assertSame(
            'Поле timezone обязательно должно присутствовать в запросе.',
            $rule->message('timezone')
        );

        $custom = new PresentRule([], 'Параметр timezone обязателен');
        $this->assertSame('Параметр timezone обязателен', $custom->message('timezone'));
    }
}
