<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation\Rules;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Rules\UrlRule;
use PHPUnit\Framework\TestCase;

class UrlRuleTest extends TestCase
{
    public function testImplementsRuleInterface(): void
    {
        $rule = new UrlRule();
        $this->assertInstanceOf(RuleInterface::class, $rule);
    }

    public function testRejectsNonStringOrEmpty(): void
    {
        $rule = new UrlRule();
        $this->assertFalse($rule->passes(null, 'website'));
        $this->assertFalse($rule->passes(['https://example.com'], 'website'));
        $this->assertFalse($rule->passes('', 'website'));
        $this->assertFalse($rule->passes('   ', 'website'));
    }

    public function testAcceptsValidHttpAndHttpsUrlsByDefault(): void
    {
        $rule = new UrlRule();
        $this->assertTrue($rule->passes('https://example.com', 'website'));
        $this->assertTrue($rule->passes('http://example.com/path?arg=value#anchor', 'website'));
        $this->assertTrue($rule->passes('https://sub.domain.org:8080/api', 'website'));
    }

    public function testRejectsDisallowedSchemesByDefault(): void
    {
        $rule = new UrlRule();
        $this->assertFalse($rule->passes('ftp://example.com/file', 'website'));
        $this->assertFalse($rule->passes('javascript:alert(1)', 'website'));
        $this->assertFalse($rule->passes('mailto:test@example.com', 'website'));
    }

    public function testAllowsCustomSchemes(): void
    {
        $rule = new UrlRule(allowedSchemes: ['ftp', 'sftp']);
        $this->assertTrue($rule->passes('ftp://example.com/file', 'website'));
        $this->assertTrue($rule->passes('sftp://example.com/file', 'website'));
        $this->assertFalse($rule->passes('https://example.com', 'website'));
    }

    public function testAllowsAnyValidSchemeWhenSchemesEmpty(): void
    {
        $rule = new UrlRule(allowedSchemes: []);
        $this->assertTrue($rule->passes('ftp://example.com', 'website'));
        $this->assertTrue($rule->passes('https://example.com', 'website'));
    }

    public function testDefaultAndCustomMessage(): void
    {
        $rule = new UrlRule();
        $this->assertSame('Поле website должно содержать корректный URL-адрес.', $rule->message('website'));

        $custom = new UrlRule(customMessage: 'Неверная ссылка');
        $this->assertSame('Неверная ссылка', $custom->message('website'));
    }
}
