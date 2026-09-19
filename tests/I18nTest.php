<?php

declare(strict_types=1);

namespace Nqphp\Tests;

use Nqphp\Core\I18n\Translator;
use Nqphp\Core\Middleware\LocaleMiddleware;
use Nqphp\Core\Middleware\RequestHandlerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class I18nTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nqphp_i18n_' . uniqid();
        mkdir($this->tempDir . '/en', 0777, true);
        mkdir($this->tempDir . '/ru', 0777, true);

        file_put_contents($this->tempDir . '/en/messages.php', '<?php return [
            "welcome" => "Welcome, :name!",
            "auth" => [
                "login" => "Sign in to {app}"
            ]
        ];');

        file_put_contents($this->tempDir . '/ru/messages.php', '<?php return [
            "welcome" => "Добро пожаловать, :name!",
            "auth" => [
                "login" => "Войти в {app}"
            ]
        ];');
    }

    protected function tearDown(): void
    {
        @unlink($this->tempDir . '/en/messages.php');
        @unlink($this->tempDir . '/ru/messages.php');
        @rmdir($this->tempDir . '/en');
        @rmdir($this->tempDir . '/ru');
        @rmdir($this->tempDir);
    }

    public function testTranslationAndInterpolation(): void
    {
        $t = new Translator($this->tempDir, 'en', 'en');

        $this->assertSame('Welcome, Alice!', $t->trans('messages.welcome', ['name' => 'Alice']));
        $this->assertSame('Sign in to nqphp', $t->trans('messages.auth.login', ['app' => 'nqphp']));

        // Switch to Russian
        $t->setLocale('ru');
        $this->assertSame('Добро пожаловать, Alice!', $t->trans('messages.welcome', ['name' => 'Alice']));
        $this->assertSame('Войти в nqphp', $t->trans('messages.auth.login', ['app' => 'nqphp']));

        // Explicit locale override
        $this->assertSame('Welcome, Bob!', $t->trans('messages.welcome', ['name' => 'Bob'], 'en'));
    }

    public function testFallbackLocale(): void
    {
        $t = new Translator($this->tempDir, 'fr', 'en');
        // 'fr' does not exist, should fallback to 'en'
        $this->assertSame('Welcome, Alice!', $t->trans('messages.welcome', ['name' => 'Alice']));
        // Unknown key returns the key itself
        $this->assertSame('messages.unknown_key', $t->trans('messages.unknown_key'));
    }

    public function testLocaleMiddleware(): void
    {
        $t = new Translator($this->tempDir, 'en', 'en');
        $middleware = new LocaleMiddleware($t, ['en', 'ru'], 'lang');

        $handler = function (Request $request): Response {
            return new Response('OK');
        };

        // 1. Query parameter ?lang=ru
        $req = Request::create('/?lang=ru');
        $res = $middleware->process($req, $handler);

        $this->assertSame('ru', $t->getLocale());
        $this->assertSame('ru', $res->headers->get('Content-Language'));

        // 2. Accept-Language header
        $req2 = Request::create('/');
        $req2->headers->set('Accept-Language', 'en-US,en;q=0.9');
        $res2 = $middleware->process($req2, $handler);

        $this->assertSame('en', $t->getLocale());
        $this->assertSame('en', $res2->headers->get('Content-Language'));
    }
}
