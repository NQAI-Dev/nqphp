<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\ProhibitedUnlessRule;
use PHPUnit\Framework\TestCase;

class ProhibitedUnlessRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new ProhibitedUnlessRule('type', 'custom', []);
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testAllowsValueWhenConditionIsMet(): void
    {
        $data = ['type' => 'custom'];
        $rule = new ProhibitedUnlessRule('type', 'custom', $data);

        $this->assertTrue($rule->passes('custom-details', 'extra'));
    }

    public function testProhibitsValueWhenConditionIsNotMet(): void
    {
        $data = ['type' => 'default'];
        $rule = new ProhibitedUnlessRule('type', 'custom', $data);

        $this->assertFalse($rule->passes('custom-details', 'extra'));
        $this->assertFalse($rule->passes(0, 'extra'));
        $this->assertFalse($rule->passes(['key' => 'val'], 'extra'));
        $this->assertTrue($rule->passes(null, 'extra'));
        $this->assertTrue($rule->passes('', 'extra'));
        $this->assertTrue($rule->passes('   ', 'extra'));
        $this->assertTrue($rule->passes([], 'extra'));
    }

    public function testMultipleAllowedValues(): void
    {
        $data = ['role' => 'editor'];
        $rule = new ProhibitedUnlessRule('role', ['admin', 'editor'], $data);

        $this->assertTrue($rule->passes('draft', 'article_status'));

        $dataGuest = ['role' => 'guest'];
        $ruleGuest = new ProhibitedUnlessRule('role', ['admin', 'editor'], $dataGuest);
        $this->assertFalse($ruleGuest->passes('draft', 'article_status'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new ProhibitedUnlessRule('status', 'open', []);
        $this->assertSame(
            'Поле comment запрещено для заполнения, если status не соответствует разрешенному значению.',
            $rule->message('comment')
        );

        $custom = new ProhibitedUnlessRule('status', 'open', [], 'Комментарии разрешены только при открытом статусе');
        $this->assertSame('Комментарии разрешены только при открытом статусе', $custom->message('comment'));
    }
}
