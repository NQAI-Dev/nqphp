<?php

declare(strict_types=1);

namespace Nqphp\Feature\Hello\Controller;

use Nqphp\Core\Attribute\Controller;
use Nqphp\Core\Attribute\Route;
use Nqphp\Core\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hello feature — minimal demonstration that the framework's
 * auto-discovery, routing, and controller-dispatch pipeline works.
 *
 * Hits:
 *   GET /                  → plain HTML "Hello World"
 *   GET /json              → JSON {"hello": "world"}
 *   GET /json/\{name\}      → JSON {"hello": "{name}"}
 */
#[Controller(prefix: '/')]
final class HelloController extends AbstractController
{
    #[Route(path: '/', methods: ['GET'], name: 'index')]
    public function index(): Response
    {
        return $this->render('<!doctype html><html><body><h1>Hello World</h1><p>nqphp works.</p></body></html>');
    }

    #[Route(path: '/json', methods: ['GET'], name: 'json')]
    public function json(): Response
    {
        return $this->json(['hello' => 'world']);
    }

    #[Route(path: '/json/{name}', methods: ['GET'], name: 'json_name')]
    public function jsonWithName(string $name): Response
    {
        return $this->json(['hello' => $name]);
    }
}
