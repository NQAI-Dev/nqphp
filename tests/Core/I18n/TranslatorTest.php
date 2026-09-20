<?php

declare(strict_types=1);

namespace Nqphp\Tests\Core\I18n;

use Nqphp\Core\I18n\Translator;
use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase
{
    private string $translationsDir;

    protected function setUp(): void
    {
        $this->translationsDir = sys_get_temp_dir() . '/nqphp_i18n_tests_' . uniqid();
        @mkdir($this->translationsDir . '/en', 0777, true);
        @mkdir($this->translationsDir . '/ru', 0777, true);

        file_put_contents($this->translationsDir . '/en/messages.php', '<?php return [
            "welcome" => "Welcome, :name!",
            "nested" => [
                "subtitle" => "Sub: {value}",
            ],
        ];');

        file_put_contents($this->translationsDir . '/ru/messages.php', '<?php return [
            "welcome" => "Добро пожаловать, :name!",
        ];');

        file_put_contents($this->translationsDir . '/auth.en.php', '<?php return [
            "failed" => "These credentials do not match our records.",
        ];');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->translationsDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = scandir($dir);
        if ($files === false) {
            return;
        }
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testTranslateDefaultLocale(): void
    {
        $translator = new Translator($this->translationsDir, 'en', 'en');

        $this->assertSame('Welcome, Alice!', $translator->trans('messages.welcome', ['name' => 'Alice']));
        $this->assertSame('Sub: test', $translator->trans('messages.nested.subtitle', ['value' => 'test']));
    }

    public function testTranslateExplicitLocale(): void
    {
        $translator = new Translator($this->translationsDir, 'en', 'en');

        $this->assertSame('Добро пожаловать, Боб!', $translator->trans('messages.welcome', ['name' => 'Боб'], 'ru'));
    }

    public function testFallbackLocaleOnMissingKey(): void
    {
        $translator = new Translator($this->translationsDir, 'ru', 'en');

        $this->assertSame('Sub: fallback', $translator->trans('messages.nested.subtitle', ['value' => 'fallback'], 'ru'));
    }

    public function testReturnsKeyWhenTranslationNotFound(): void
    {
        $translator = new Translator($this->translationsDir, 'en', 'en');

        $this->assertSame('nonexistent.key', $translator->trans('nonexistent.key'));
    }

    public function testSuffixFilePattern(): void
    {
        $translator = new Translator($this->translationsDir, 'en', 'en');

        $this->assertSame('These credentials do not match our records.', $translator->trans('auth.failed'));
    }

    public function testSetAndGetLocale(): void
    {
        $translator = new Translator($this->translationsDir, 'en', 'en');
        $this->assertSame('en', $translator->getLocale());

        $translator->setLocale('ru');
        $this->assertSame('ru', $translator->getLocale());
        $this->assertSame('en', $translator->getFallbackLocale());
    }

    public function testHasMethod(): void
    {
        $translator = new Translator($this->translationsDir, 'en', 'en');

        $this->assertTrue($translator->has('messages.welcome'));
        $this->assertTrue($translator->has('auth.failed'));
        $this->assertFalse($translator->has('unknown.phrase'));
    }

    public function testTransChoiceEnglishPluralization(): void
    {
        file_put_contents($this->translationsDir . '/en/messages.php', '<?php return [
            "apples" => "There is one apple|There are :count apples",
        ];');

        $translator = new Translator($this->translationsDir, 'en', 'en');

        $this->assertSame('There is one apple', $translator->transChoice('messages.apples', 1));
        $this->assertSame('There are 0 apples', $translator->transChoice('messages.apples', 0));
        $this->assertSame('There are 5 apples', $translator->transChoice('messages.apples', 5));
    }

    public function testTransChoiceRussianPluralization(): void
    {
        file_put_contents($this->translationsDir . '/ru/messages.php', '<?php return [
            "apples" => ":count яблоко|:count яблока|:count яблок",
        ];');

        $translator = new Translator($this->translationsDir, 'ru', 'en');

        $this->assertSame('1 яблоко', $translator->transChoice('messages.apples', 1));
        $this->assertSame('21 яблоко', $translator->transChoice('messages.apples', 21));
        $this->assertSame('2 яблока', $translator->transChoice('messages.apples', 2));
        $this->assertSame('24 яблока', $translator->transChoice('messages.apples', 24));
        $this->assertSame('5 яблок', $translator->transChoice('messages.apples', 5));
        $this->assertSame('11 яблок', $translator->transChoice('messages.apples', 11));
        $this->assertSame('0 яблок', $translator->transChoice('messages.apples', 0));
    }
}
