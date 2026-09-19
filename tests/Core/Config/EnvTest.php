<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\Config;

use Nqphp\Core\Config\Env;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EnvTest extends TestCase
{
    private string $tempEnvFile;

    protected function setUp(): void
    {
        Env::reset();
        $this->tempEnvFile = sys_get_temp_dir() . '/.env.test.' . uniqid();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempEnvFile)) {
            unlink($this->tempEnvFile);
        }
        Env::reset();
    }

    public function testParsesEnvLinesAndCastsTypes(): void
    {
        $content = <<<ENV
# Comment line
APP_ENV=production
APP_DEBUG=false
APP_PORT=8080
APP_RATIO=1.75
APP_NAME="NQPHP Framework"
APP_SECRET='secret-key'
EMPTY_VAL=null
DEFAULT_EMPTY=empty
ENV;

        file_put_contents($this->tempEnvFile, $content);
        Env::load($this->tempEnvFile);

        $this->assertSame('production', Env::get('APP_ENV'));
        $this->assertFalse(Env::get('APP_DEBUG'));
        $this->assertSame(8080, Env::get('APP_PORT'));
        $this->assertSame(1.75, Env::get('APP_RATIO'));
        $this->assertSame('NQPHP Framework', Env::get('APP_NAME'));
        $this->assertSame('secret-key', Env::get('APP_SECRET'));
        $this->assertNull(Env::get('EMPTY_VAL'));
        $this->assertSame('', Env::get('DEFAULT_EMPTY'));
    }

    public function testGetFallbackWhenNotSet(): void
    {
        $this->assertSame('default_value', Env::get('NON_EXISTENT_KEY_123', 'default_value'));
        $this->assertNull(Env::get('NON_EXISTENT_KEY_123'));
    }

    public function testGetOrFailThrowsWhenMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Environment variable [DATABASE_URL] is not set.');

        Env::getOrFail('DATABASE_URL');
    }
}
