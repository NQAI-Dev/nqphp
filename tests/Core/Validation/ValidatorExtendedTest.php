<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Validation;

use Nqphp\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorExtendedTest extends TestCase
{
    private function v(): Validator
    {
        return new Validator();
    }

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

    // ─── uuid ─────────────────────────────────────────────────────────────────

    public function testUuidValid(): void
    {
        $e = $this->v()->validate(['id' => '123e4567-e89b-12d3-a456-426614174000'], ['id' => 'uuid']);
        $this->assertSame([], $e);
    }

    public function testUuidInvalid(): void
    {
        $e = $this->v()->validate(['id' => 'not-a-valid-uuid'], ['id' => 'uuid']);
        $this->assertArrayHasKey('id', $e);
        $this->assertSame('The id field must be a valid UUID.', $e['id'][0]);
    }

    // ─── json ─────────────────────────────────────────────────────────────────

    public function testJsonValid(): void
    {
        $e = $this->v()->validate(['meta' => '{"name":"Alice","age":30}'], ['meta' => 'json']);
        $this->assertSame([], $e);
    }

    public function testJsonInvalid(): void
    {
        $e = $this->v()->validate(['meta' => '{broken:json}'], ['meta' => 'json']);
        $this->assertArrayHasKey('meta', $e);
        $this->assertSame('The meta field must be a valid JSON string.', $e['meta'][0]);
    }

    // ─── ip ───────────────────────────────────────────────────────────────────

    public function testIpValid(): void
    {
        $e = $this->v()->validate(['ip_v4' => '192.168.1.1', 'ip_v6' => '::1'], ['ip_v4' => 'ip', 'ip_v6' => 'ip']);
        $this->assertSame([], $e);
    }

    public function testIpInvalid(): void
    {
        $e = $this->v()->validate(['ip' => '999.999.999.999'], ['ip' => 'ip']);
        $this->assertArrayHasKey('ip', $e);
        $this->assertSame('The ip field must be a valid IP address.', $e['ip'][0]);
    }

    // ─── mac ──────────────────────────────────────────────────────────────────

    public function testMacValid(): void
    {
        $e = $this->v()->validate(['mac' => '00:1A:2B:3C:4D:5E'], ['mac' => 'mac']);
        $this->assertSame([], $e);
    }

    public function testMacInvalid(): void
    {
        $e = $this->v()->validate(['mac' => 'invalid-mac-address'], ['mac' => 'mac']);
        $this->assertArrayHasKey('mac', $e);
        $this->assertSame('The mac field must be a valid MAC address.', $e['mac'][0]);
    }

    // ─── starts_with / ends_with ──────────────────────────────────────────────

    public function testStartsWithValid(): void
    {
        $e = $this->v()->validate(['url' => 'https://example.com'], ['url' => 'starts_with:http://,https://']);
        $this->assertSame([], $e);
    }

    public function testStartsWithInvalid(): void
    {
        $e = $this->v()->validate(['url' => 'ftp://example.com'], ['url' => 'starts_with:http://,https://']);
        $this->assertArrayHasKey('url', $e);
        $this->assertSame('The url field must start with one of: http://,https://.', $e['url'][0]);
    }

    public function testEndsWithValid(): void
    {
        $e = $this->v()->validate(['file' => 'document.pdf'], ['file' => 'ends_with:.pdf,.docx']);
        $this->assertSame([], $e);
    }

    public function testEndsWithInvalid(): void
    {
        $e = $this->v()->validate(['file' => 'archive.zip'], ['file' => 'ends_with:.pdf,.docx']);
        $this->assertArrayHasKey('file', $e);
        $this->assertSame('The file field must end with one of: .pdf,.docx.', $e['file'][0]);
    }
}
