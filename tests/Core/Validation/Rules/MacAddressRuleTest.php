<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\MacAddressRule;
use PHPUnit\Framework\TestCase;

class MacAddressRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new MacAddressRule();
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsNonStringOrEmpty(): void
    {
        $rule = new MacAddressRule();
        $this->assertFalse($rule->passes(null, 'mac'));
        $this->assertFalse($rule->passes([], 'mac'));
        $this->assertFalse($rule->passes(12345, 'mac'));
        $this->assertFalse($rule->passes('', 'mac'));
        $this->assertFalse($rule->passes('   ', 'mac'));
    }

    public function testAcceptsValidMacFormats(): void
    {
        $rule = new MacAddressRule();
        // Colon-separated
        $this->assertTrue($rule->passes('00:1A:2B:3C:4D:5E', 'mac'));
        $this->assertTrue($rule->passes('00:1a:2b:3c:4d:5e', 'mac'));
        // Hyphen-separated
        $this->assertTrue($rule->passes('00-1A-2B-3C-4D-5E', 'mac'));
        $this->assertTrue($rule->passes('00-1a-2b-3c-4d-5e', 'mac'));
        // Cisco dot-separated
        $this->assertTrue($rule->passes('001A.2B3C.4D5E', 'mac'));
        $this->assertTrue($rule->passes('001a.2b3c.4d5e', 'mac'));
    }

    public function testRejectsInvalidMacAddresses(): void
    {
        $rule = new MacAddressRule();
        $this->assertFalse($rule->passes('00:1A:2B:3C:4D', 'mac'));
        $this->assertFalse($rule->passes('00:1A:2B:3C:4D:5E:6F', 'mac'));
        $this->assertFalse($rule->passes('00:1G:2B:3C:4D:5E', 'mac'));
        $this->assertFalse($rule->passes('001A-2B3C-4D5E', 'mac'));
        $this->assertFalse($rule->passes('random-string', 'mac'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new MacAddressRule();
        $this->assertSame('Поле mac_address должно содержать корректный MAC-адрес.', $rule->message('mac_address'));

        $custom = new MacAddressRule('Неверный формат MAC');
        $this->assertSame('Неверный формат MAC', $custom->message('mac_address'));
    }
}
