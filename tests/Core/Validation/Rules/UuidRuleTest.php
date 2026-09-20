<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\UuidRule;
use PHPUnit\Framework\TestCase;

class UuidRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new UuidRule();
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsNonString(): void
    {
        $rule = new UuidRule();
        $this->assertFalse($rule->passes(12345, 'uuid'));
        $this->assertFalse($rule->passes(null, 'uuid'));
        $this->assertFalse($rule->passes([], 'uuid'));
    }

    public function testRejectsMalformedString(): void
    {
        $rule = new UuidRule();
        $this->assertFalse($rule->passes('not-a-uuid', 'id'));
        $this->assertFalse($rule->passes('12345678-1234-1234-1234-1234567890123', 'id'));
        $this->assertFalse($rule->passes('xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx', 'id'));
    }

    public function testAcceptsValidGenericUuids(): void
    {
        $rule = new UuidRule();
        // v4
        $this->assertTrue($rule->passes('f47ac10b-58cc-4372-a567-0e02b2c3d479', 'id'));
        // uppercase v4
        $this->assertTrue($rule->passes('F47AC10B-58CC-4372-A567-0E02B2C3D479', 'id'));
        // v1
        $this->assertTrue($rule->passes('2c8f8b8a-3607-11eb-adc1-0242ac120002', 'id'));
    }

    public function testAcceptsSpecificVersion(): void
    {
        $v4Rule = new UuidRule(version: 4);
        $v1Rule = new UuidRule(version: 1);

        $v4Uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $v1Uuid = '2c8f8b8a-3607-11eb-adc1-0242ac120002';

        $this->assertTrue($v4Rule->passes($v4Uuid, 'id'));
        $this->assertFalse($v4Rule->passes($v1Uuid, 'id'));

        $this->assertTrue($v1Rule->passes($v1Uuid, 'id'));
        $this->assertFalse($v1Rule->passes($v4Uuid, 'id'));
    }

    public function testUnsupportedVersionRejects(): void
    {
        $invalidRule = new UuidRule(version: 99);
        $this->assertFalse($invalidRule->passes('f47ac10b-58cc-4372-a567-0e02b2c3d479', 'id'));
    }

    public function testMessages(): void
    {
        $genericRule = new UuidRule();
        $this->assertSame('Поле id должно быть корректным UUID.', $genericRule->message('id'));

        $v4Rule = new UuidRule(version: 4);
        $this->assertSame('Поле id должно быть корректным UUID версии 4.', $v4Rule->message('id'));
    }
}
