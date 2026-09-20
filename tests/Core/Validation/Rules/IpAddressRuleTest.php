<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\IpAddressRule;
use PHPUnit\Framework\TestCase;

class IpAddressRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new IpAddressRule();
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsNonStringOrEmpty(): void
    {
        $rule = new IpAddressRule();
        $this->assertFalse($rule->passes(127001, 'ip'));
        $this->assertFalse($rule->passes(null, 'ip'));
        $this->assertFalse($rule->passes('', 'ip'));
        $this->assertFalse($rule->passes([], 'ip'));
    }

    public function testAcceptsValidIpv4AndIpv6ByDefault(): void
    {
        $rule = new IpAddressRule();
        $this->assertTrue($rule->passes('192.168.1.1', 'ip'));
        $this->assertTrue($rule->passes('8.8.8.8', 'ip'));
        $this->assertTrue($rule->passes('2001:0db8:85a3:0000:0000:8a2e:0370:7334', 'ip'));
        $this->assertTrue($rule->passes('::1', 'ip'));
        $this->assertFalse($rule->passes('256.256.256.256', 'ip'));
        $this->assertFalse($rule->passes('not.an.ip', 'ip'));
    }

    public function testFiltersByIpv4Only(): void
    {
        $rule = new IpAddressRule(type: IpAddressRule::TYPE_V4);
        $this->assertTrue($rule->passes('1.1.1.1', 'ip'));
        $this->assertFalse($rule->passes('::1', 'ip'));
    }

    public function testFiltersByIpv6Only(): void
    {
        $rule = new IpAddressRule(type: IpAddressRule::TYPE_V6);
        $this->assertTrue($rule->passes('2001:db8::1', 'ip'));
        $this->assertFalse($rule->passes('1.1.1.1', 'ip'));
    }

    public function testRejectsPrivateRangesWhenConfigured(): void
    {
        $rule = new IpAddressRule(allowPrivate: false);
        $this->assertFalse($rule->passes('192.168.0.1', 'ip'));
        $this->assertFalse($rule->passes('10.0.0.1', 'ip'));
        $this->assertFalse($rule->passes('172.16.0.1', 'ip'));
        $this->assertTrue($rule->passes('8.8.8.8', 'ip'));
    }

    public function testRejectsReservedRangesWhenConfigured(): void
    {
        $rule = new IpAddressRule(allowReserved: false);
        $this->assertFalse($rule->passes('0.0.0.0', 'ip'));
        $this->assertFalse($rule->passes('240.0.0.1', 'ip'));
        $this->assertTrue($rule->passes('8.8.8.8', 'ip'));
    }

    public function testMessages(): void
    {
        $anyRule = new IpAddressRule();
        $this->assertSame('Поле client_ip должно быть корректным IP-адресом.', $anyRule->message('client_ip'));

        $v4Rule = new IpAddressRule(type: IpAddressRule::TYPE_V4);
        $this->assertSame('Поле client_ip должно быть корректным IPv4-адресом.', $v4Rule->message('client_ip'));

        $v6Rule = new IpAddressRule(type: IpAddressRule::TYPE_V6);
        $this->assertSame('Поле client_ip должно быть корректным IPv6-адресом.', $v6Rule->message('client_ip'));
    }
}
