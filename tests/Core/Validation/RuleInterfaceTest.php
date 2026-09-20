<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation;

use Nqphp\Core\Validation\RuleInterface;
use Nqphp\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

class UppercaseRule implements RuleInterface
{
    public function passes(mixed $value, string $field): bool
    {
        return is_string($value) && strtoupper($value) === $value;
    }

    public function message(string $field): string
    {
        return "The {$field} field must be uppercase.";
    }
}

class RuleInterfaceTest extends TestCase
{
    public function testValidatesWithRuleInterfaceInArrayRules(): void
    {
        $validator = new Validator();
        $rule = new UppercaseRule();

        // Valid data
        $errors = $validator->validate(['code' => 'HELLO'], [
            'code' => ['required', $rule],
        ]);
        $this->assertEmpty($errors);

        // Invalid data
        $errors = $validator->validate(['code' => 'hello'], [
            'code' => ['required', $rule],
        ]);
        $this->assertArrayHasKey('code', $errors);
        $this->assertContains('The code field must be uppercase.', $errors['code']);
    }

    public function testAddRuleWithRuleInterface(): void
    {
        $validator = new Validator();
        $validator->addRule(new UppercaseRule());

        $errors = $validator->validate(['code' => 'TEST'], [
            'code' => ['required', UppercaseRule::class],
        ]);
        $this->assertEmpty($errors);

        $errors = $validator->validate(['code' => 'test'], [
            'code' => ['required', UppercaseRule::class],
        ]);
        $this->assertArrayHasKey('code', $errors);
        $this->assertContains('The code field must be uppercase.', $errors['code']);
    }
}
