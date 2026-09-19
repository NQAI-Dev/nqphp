<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Security;

use InvalidArgumentException;
use Nqphp\Core\Security\NativePasswordHasher;
use PHPUnit\Framework\TestCase;

class NativePasswordHasherTest extends TestCase
{
    public function testHashAndVerify(): void
    {
        $hasher = new NativePasswordHasher(PASSWORD_BCRYPT, ['cost' => 10]);
        $plain = 'secret-password-123';

        $hash = $hasher->hash($plain);

        $this->assertNotEmpty($hash);
        $this->assertNotSame($plain, $hash);
        $this->assertTrue($hasher->verify($plain, $hash));
        $this->assertFalse($hasher->verify('wrong-password', $hash));
    }

    public function testEmptyPasswordThrowsOnHash(): void
    {
        $hasher = new NativePasswordHasher();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password cannot be empty.');

        $hasher->hash('');
    }

    public function testVerifyWithEmptyInputsReturnsFalse(): void
    {
        $hasher = new NativePasswordHasher();

        $this->assertFalse($hasher->verify('', 'some-hash'));
        $this->assertFalse($hasher->verify('secret', ''));
    }

    public function testNeedsRehashDetectsCostChange(): void
    {
        $hasherCost10 = new NativePasswordHasher(PASSWORD_BCRYPT, ['cost' => 10]);
        $hash = $hasherCost10->hash('my-password');

        $this->assertFalse($hasherCost10->needsRehash($hash));

        $hasherCost12 = new NativePasswordHasher(PASSWORD_BCRYPT, ['cost' => 12]);
        $this->assertTrue($hasherCost12->needsRehash($hash));
    }
}
