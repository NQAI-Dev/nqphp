<?php

declare(strict_types=1);

namespace Nqphp\Fixture\Home\Controller;

use Nqphp\Core\Attribute\Controller;
use Nqphp\Core\Attribute\Route;
use Nqphp\Core\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

#[Controller]
class HomeController extends AbstractController
{
    #[Route('/home', name: 'home', methods: ['GET'])]
    public function home(): Response
    {
        return new Response('home page', 200);
    }

    #[Route('/go-home', name: 'go_home', methods: ['GET'])]
    public function goHome(): RedirectResponse
    {
        return $this->redirectToRoute('home');
    }

    #[Route('/go-home-permanent', name: 'go_home_permanent', methods: ['GET'])]
    public function goHomePermanent(): RedirectResponse
    {
        return $this->redirectToRoute('home', status: 301);
    }

    #[Route('/blog/{slug}', name: 'blog_show', methods: ['GET'])]
    public function blogShow(string $slug): Response
    {
        return new Response("post: {$slug}", 200);
    }

    #[Route('/go-blog', name: 'go_blog', methods: ['GET'])]
    public function goBlog(): RedirectResponse
    {
        return $this->redirectToRoute('blog_show', ['slug' => 'hello-world']);
    }
}
