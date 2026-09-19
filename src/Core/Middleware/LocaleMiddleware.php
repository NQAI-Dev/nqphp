<?php

declare(strict_types=1);

namespace Nqphp\Core\Middleware;

use Nqphp\Core\I18n\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class LocaleMiddleware implements MiddlewareInterface
{
    private TranslatorInterface $translator;
    /** @var list<string> */
    private array $supportedLocales;
    private string $queryParam;
    private string $sessionKey;

    /**
     * @param TranslatorInterface $translator
     * @param list<string> $supportedLocales Allowed locales (e.g. ['en', 'ru'])
     * @param string $queryParam Query parameter name for switching locale (e.g. '?lang=ru')
     * @param string $sessionKey Session attribute name to remember locale
     */
    public function __construct(
        TranslatorInterface $translator,
        array $supportedLocales = ['en', 'ru'],
        string $queryParam = 'lang',
        string $sessionKey = '_locale'
    ) {
        $this->translator = $translator;
        $this->supportedLocales = $supportedLocales;
        $this->queryParam = $queryParam;
        $this->sessionKey = $sessionKey;
    }

    public function process(Request $request, callable $next): Response
    {
        $chosenLocale = null;

        // 1. Check query parameter (e.g., ?lang=ru)
        if ($request->query->has($this->queryParam)) {
            $candidate = (string) $request->query->get($this->queryParam);
            if (in_array($candidate, $this->supportedLocales, true)) {
                $chosenLocale = $candidate;
                if ($request->hasSession()) {
                    $request->getSession()->set($this->sessionKey, $candidate);
                }
            }
        }

        // 2. Check active session
        if ($chosenLocale === null && $request->hasSession()) {
            $candidate = (string) $request->getSession()->get($this->sessionKey, '');
            if (in_array($candidate, $this->supportedLocales, true)) {
                $chosenLocale = $candidate;
            }
        }

        // 3. Check Accept-Language header
        if ($chosenLocale === null) {
            $preferred = $request->getPreferredLanguage($this->supportedLocales);
            if ($preferred !== null && in_array($preferred, $this->supportedLocales, true)) {
                $chosenLocale = $preferred;
            }
        }

        if ($chosenLocale !== null) {
            $this->translator->setLocale($chosenLocale);
            $request->setLocale($chosenLocale);
        }

        $response = $next($request);

        // Append Content-Language header
        $response->headers->set('Content-Language', $this->translator->getLocale());

        return $response;
    }
}
