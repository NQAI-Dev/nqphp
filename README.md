# nqphp

> Modular monolith PHP framework. Symfony components under the hood.
> Vertical Slice Architecture. JS modules per controller.

## Status: pre-alpha Phase 2

`nqphp` is now a **pure framework package** — only `src/Core/` ships
in this repo. Concrete features (controllers, commands, jobs, etc.)
live in **separate application repositories** that `composer require
nqai-dev/nqphp`. The canonical reference scaffold lives in
[`nqai-dev/nqphp-template`](https://github.com/NQAI-Dev/nqphp-template)
and demonstrates the framework's conventions.

What ships in this repo:

* `src/Core/Attribute/` — `#[Controller]`, `#[Route]`, `#[AsCommand]`,
  `#[Middleware]`, `#[Schedule]`, plus more Phase 2 attributes
* `src/Core/Routing/` — route auto-discovery + named groups
* `src/Core/Kernel/` — minimal HTTP kernel; runs discovered middlewares
  around route dispatch
* `src/Core/Console/` — Symfony Console wrapper + `CommandDiscoverer`
* `src/Core/Middleware/` — auto-discovery + Kernel invocation
* `src/Core/Scheduler/` — `#[Schedule]` attribute + Symfony Scheduler wiring
* `src/Core/Config/` — per-feature `config.php` reader + `Kernel::config()`
* `src/Core/Js/` — JS-module runtime helper + per-feature `client.js`
  ES-module serving
* `src/Core/Security/` — double-submit-cookie CSRF (`CsrfTokenManager`)
* `src/Core/Controller/` — base class with `render / redirect / json`

## Architecture decisions

* **Framework + apps are separate repos.** Framework code ships here
  as a Composer package; consumer apps live in their own repos with
  their own `composer.json` that `require`s `nqai-dev/nqphp`. The
  scaffold repo [`nqphp-template`](https://github.com/NQAI-Dev/nqphp-template)
  demonstrates the convention end-to-end.

* **Vertical Slice Architecture.** Each feature in a consumer app is
  a directory under `src/Feature/{Name}/` containing its own
  `Controller/`, `Command/`, `Middleware/`, `Scheduler/`, and
  `config.php`. The framework discovers these at boot via reflection.
  A feature is the unit of isolation and (eventually) extraction.

* **Symfony components as building blocks.** `symfony/routing`,
  `symfony/http-kernel`, `symfony/console`, `symfony/dependency-injection`,
  `symfony/scheduler`. Not `framework-bundle` — too opinionated. We
  glue components together.

* **Auto-discovery via attributes.** `#[Controller]` on the class,
  `#[Route]` on the method. No route files. `#[AsCommand]` for CLI.
  `#[Middleware]` for cross-cutting concerns. `#[Schedule]` for
  periodic tasks. `composer scripts` for cs-fixer style checks.

* **Named routes.** Every route has a name like `admin:post:list`
  derived from the `#[Controller]` prefix. Manage thousands of routes
  via `bin/console routes:list` and per-prefix filters.

* **JS modules per controller.** Each controller directory can contain
  a `client.js` that the framework serves as an ES module at
  `/_nqphp/js/{Name}/{file}.js`. HTML works without JS; the module
  progressively enhances interaction. No SPA framework.

* **DX is a product.** `bin/dev` orchestrates `composer install` +
  PHP built-in server. `composer cs:check` / `composer cs:fix`
  enforce PSR-12 via php-cs-fixer (CI step).

* **MIT license.**

## Installation (in a consumer app)

```bash
composer require nqai-dev/nqphp:^0.2
```

Then in your `public/index.php`:

```php
require __DIR__ . '/../vendor/autoload.php';
use Nqphp\Core\Kernel\Kernel;
use Symfony\Component\HttpFoundation\Request;

$kernel = new Kernel(dirname(__DIR__));
$kernel->handle(Request::createFromGlobals())->send();
```

In your `bin/console`:

```php
require __DIR__ . '/../vendor/autoload.php';
use Nqphp\Core\Console\CommandDiscoverer;
use Nqphp\Core\Routing\Router;
use Nqphp\Core\Scheduler\ScheduleDiscoverer;
use Nqphp\Core\Middleware\MiddlewareDiscoverer;
use Symfony\Component\Console\Application;

$projectDir = dirname(__DIR__);
$kernel = new Kernel($projectDir);
$app = new Application('myapp', '0.1.0');
$app->add(new RoutesListCommand($kernel));
// ... add your own commands + the discoverers' output
$app->run();
```

See `nqphp-template` for the full scaffolded `bin/console` and
`public/index.php` ready to extend.

## Available CLI commands (built-in)

```
bin/console routes:list          List all auto-discovered HTTP routes.
bin/console schedule:list       List all #[Schedule]-annotated tasks.
bin/console schedule:run        Run schedules whose cron is due now.
bin/console feature:list        Per-feature inventory: config + routes + middlewares.
bin/console list                 Show Symfony Console's auto-generated help.
```

(`hello:greet` and any other feature commands are *not* shipped by
the framework — they live in your app's `src/Feature/{Name}/Command/`
and are discovered at boot.)

## Phase 2 status (framework-side)

* ✅ `#[AsCommand]` CLI command auto-discovery (`2be0a72`).
* ✅ JS-module runtime helper (`b842bd1`) — framework `csrf()`,
  `fetchJson()`, per-feature `client.js` serving.
* ✅ Middleware pipeline (`53cf94c` / `be81005` / `424a354` /
  `6df392f`) — `#[Middleware]` attribute + `MiddlewareDiscoverer` +
  Kernel runtime invocation + 4 tests.
* ✅ `#[Schedule]` cron + Symfony Scheduler integration (`475fe0b` /
  `04d0799` / `5c0ab05`) — discover + list + run via the real
  Symfony Scheduler evaluator.
* ✅ Per-feature `config.php` autoloader (`fabd4d5`) — typed
  config reads + `Kernel::config(string $feature, string $key, $default)`.
* ✅ `bin/console feature:list` (`0a8cd28`) — per-feature inventory.
* ✅ PHP CS Fixer (`cf4f33a`) — `composer cs:check` / `cs:fix`, CI step.

Open Phase 2 follow-ups (not yet shipped, lower priority):

* Full Symfony DI integration (per-feature `services.yaml` with
  actual service definitions; current per-feature `config.php` is the
  data-only half).
* `#[Entity]` attribute + Doctrine bridge.

## License

MIT. See [LICENSE](LICENSE).
