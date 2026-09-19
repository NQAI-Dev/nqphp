<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation;

use Nqphp\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorExtendedTest extends TestCase
{
    private function v(): Validator { return new Validator(); }

    // ─── url ──────────────────────────────────────────────────────────────────

    public function testUrlValid(): void
    {
        $e = $this->v()->validate(['site' => 'https://example.com'], ['site' => 'url']);
        $this->assertSame([], $e);
    }

    public function testUrlInvalid(): void
    {
        $e = $this->v()->validate(['site' => 'not-a-url'], ['site' => 'url']);
        $this->assertArrayHasKey('site', $e);
    }

    // ─── integer ──────────────────────────────────────────────────────────────

    public function testIntegerValid(): void
    {
        $e = $this->v()->validate(['n' => '42'], ['n' => 'integer']);
        $this->assertSame([], $e);
    }

    public function testIntegerInvalid(): void
    {
        $e = $this->v()->validate(['n' => '3.14'], ['n' => 'integer']);
        $this->assertArrayHasKey('n', $e);
    }

    // ─── boolean ──────────────────────────────────────────────────────────────

    public function testBooleanTrueValues(): void
    {
        foreach (['1', 'true', 'yes', 'on'] as $v) {
            $e = $this->v()->validate(['flag' => $v], ['flag' => 'boolean']);
            $this->assertSame([], $e, "Value '$v' should pass boolean");
        }
    }

    public function testBooleanInvalid(): void
    {
        $e = $this->v()->validate(['flag' => 'maybe'], ['flag' => 'boolean']);
        $this->assertArrayHasKey('flag', $e);
    }

    // ─── alpha ────────────────────────────────────────────────────────────────

    public function testAlphaValid(): void
    {
        $e = $this->v()->validate(['name' => 'John'], ['name' => 'alpha']);
        $this->assertSame([], $e);
    }

    public function testAlphaInvalid(): void
    {
        $e = $this->v()->validate(['name' => 'John123'], ['name' => 'alpha']);
        $this->assertArrayHasKey('name', $e);
    }

    // ─── alpha_num ────────────────────────────────────────────────────────────

    public function testAlphaNumValid(): void
    {
        $e = $this->v()->validate(['slug' => 'user42'], ['slug' => 'alpha_num']);
        $this->assertSame([], $e);
    }

    public function testAlphaNumInvalid(): void
    {
        $e = $this->v()->validate(['slug' => 'user-42'], ['slug' => 'alpha_num']);
        $this->assertArrayHasKey('slug', $e);
    }

    // ─── between ─────────────────────────────────────────────────────────────

    public function testBetweenNumericPass(): void
    {
        $e = $this->v()->validate(['age' => '25'], ['age' => 'between:18,60']);
        $this->assertSame([], $e);
    }

    public function testBetweenNumericFail(): void
    {
        $e = $this->v()->validate(['age' => '10'], ['age' => 'between:18,60']);
        $this->assertArrayHasKey('age', $e);
    }

    public function testBetweenStringLengthPass(): void
    {
        $e = $this->v()->validate(['bio' => 'Hello'], ['bio' => 'between:3,10']);
        $this->assertSame([], $e);
    }

    public function testBetweenStringLengthFail(): void
    {
        $e = $this->v()->validate(['bio' => 'Hi'], ['bio' => 'between:3,10']);
        $this->assertArrayHasKey('bio', $e);
    }

    // ─── in / not_in ─────────────────────────────────────────────────────────

    public function testInPass(): void
    {
        $e = $this->v()->validate(['role' => 'admin'], ['role' => 'in:admin,editor,viewer']);
        $this->assertSame([], $e);
    }

    public function testInFail(): void
    {
        $e = $this->v()->validate(['role' => 'superuser'], ['role' => 'in:admin,editor,viewer']);
        $this->assertArrayHasKey('role', $e);
    }

    public function testNotInPass(): void
    {
        $e = $this->v()->validate(['status' => 'active'], ['status' => 'not_in:banned,suspended']);
        $this->assertSame([], $e);
    }

    public function testNotInFail(): void
    {
        $e = $this->v()->validate(['status' => 'banned'], ['status' => 'not_in:banned,suspended']);
        $this->assertArrayHasKey('status', $e);
    }

    // ─── regex ────────────────────────────────────────────────────────────────

    public function testRegexPass(): void
    {
        $e = $this->v()->validate(['zip' => '12345'], ['zip' => 'regex:/^\d{5}$/']);
        $this->assertSame([], $e);
    }

    public function testRegexFail(): void
    {
        $e = $this->v()->validate(['zip' => 'ABCDE'], ['zip' => 'regex:/^\d{5}$/']);
        $this->assertArrayHasKey('zip', $e);
    }

    // ─── date / date_format ───────────────────────────────────────────────────

    public function testDatePass(): void
    {
        $e = $this->v()->validate(['dob' => '1990-05-15'], ['dob' => 'date']);
        $this->assertSame([], $e);
    }

    public function testDateFail(): void
    {
        $e = $this->v()->validate(['dob' => 'not-a-date'], ['dob' => 'date']);
        $this->assertArrayHasKey('dob', $e);
    }

    public function testDateFormatPass(): void
    {
        $e = $this->v()->validate(['ts' => '2026-09-19'], ['ts' => 'date_format:Y-m-d']);
        $this->assertSame([], $e);
    }

    public function testDateFormatFail(): void
    {
        $e = $this->v()->validate(['ts' => '19/09/2026'], ['ts' => 'date_format:Y-m-d']);
        $this->assertArrayHasKey('ts', $e);
    }

    // ─── same / different ─────────────────────────────────────────────────────

    public function testSamePass(): void
    {
        $e = $this->v()->validate(
            ['password' => 'secret', 'password_confirm' => 'secret'],
            ['password_confirm' => 'same:password']
        );
        $this->assertSame([], $e);
    }

    public function testSameFail(): void
    {
        $e = $this->v()->validate(
            ['password' => 'secret', 'password_confirm' => 'wrong'],
            ['password_confirm' => 'same:password']
        );
        $this->assertArrayHasKey('password_confirm', $e);
    }

    public function testDifferentPass(): void
    {
        $e = $this->v()->validate(
            ['old_pass' => 'old', 'new_pass' => 'new'],
            ['new_pass' => 'different:old_pass']
        );
        $this->assertSame([], $e);
    }

    public function testDifferentFail(): void
    {
        $e = $this->v()->validate(
            ['old_pass' => 'same', 'new_pass' => 'same'],
            ['new_pass' => 'different:old_pass']
        );
        $this->assertArrayHasKey('new_pass', $e);
    }

    // ─── custom rule ──────────────────────────────────────────────────────────

    public function testCustomRulePass(): void
    {
        $v = $this->v();
        $v->addRule('starts_with_a', fn ($val) => str_starts_with((string) $val, 'a') ? null : 'Must start with a.');

        $e = $v->validate(['word' => 'alpha'], ['word' => 'starts_with_a']);
        $this->assertSame([], $e);
    }

    public function testCustomRuleFail(): void
    {
        $v = $this->v();
        $v->addRule('starts_with_a', fn ($val) => str_starts_with((string) $val, 'a') ? null : 'Must start with a.');

        $e = $v->validate(['word' => 'beta'], ['word' => 'starts_with_a']);
        $this->assertArrayHasKey('word', $e);
        $this->assertSame('Must start with a.', $e['word'][0]);
    }

    // ─── passes() / fails() ───────────────────────────────────────────────────

    public function testPassesReturnsTrueOnNoErrors(): void
    {
        $v = $this->v();
        $this->assertTrue($v->passes(['email' => 'user@example.com'], ['email' => 'email']));
    }

    public function testFailsReturnsTrueOnErrors(): void
    {
        $v = $this->v();
        $this->assertTrue($v->fails(['email' => 'bad'], ['email' => 'email']));
    }
}
