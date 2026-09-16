# nqphp

> Modular monolith PHP framework. Symfony components under the hood.
> Vertical Slice Architecture. JS modules per controller.

## Status: pre-alpha Phase 2

Phase 1 shipped the minimum HTTP pipeline: `#[Controller] + #[Route]`
auto-discovery, HTTP kernel, front controller, `bin/console routes:list`.
Phase 2 adds CLI command auto-discovery via `#[AsCommand]` — commands in
`src/Feature/*/Command/` and `src/Core/Command/` are registered
automatically by `bin/console`, no hand-wiring needed.

## Architecture decisions

* **Vertical Slice Architecture.** Each feature is a directory under
  `src/Feature/{Name}/` containing its own `Controller/`, `Entity/`,
  `Job/`, `Command/`, and `config/services.yaml`. A feature is the
  unit of isolation and (eventually) extraction.
* **Symfony components as building blocks.** `symfony/routing`,
  `symfony/http-kernel`, `symfony/console`, `symfony/dependency-injection`.
  Not `framework-bundle` — too opinionated. We glue components together.
* **Auto-discovery via attributes.** `#[Controller]` on the class,
  `#[Route]` on the method. No route files. No service registration
  hand-coding (Phase 2: per-feature services.yaml autoloading).
* **Named routes.** Every route has a name like `admin:post:list`
  derived from the `#[Controller]` prefix. Manage thousands of routes
  via `bin/console routes:list` and per-prefix filters.
* **JS modules per controller.** Each controller directory can contain
  a `client.js` that the framework serves as an ES module. HTML
  works without JS; the module progressively enhances interaction
  (form submit without reload, live updates). No SPA framework.
* **MIT license.**

## Quick start (requires PHP 8.4+ and composer)

```bash
composer install
php -S localhost:8000 -t public  # then visit /, /json, /json/world
bin/console routes:list
bin/console list
```

## Hello Feature (current deliverable)

```
GET  /                HTML: <h1>Hello World</h1>
GET  /json            JSON: {"hello":"world"}
GET  /json/{name}     JSON: {"hello":"{name}"}

bin/console hello:greet [name]   Greets the world (or `name`). alias: hi
bin/console routes:list          Lists all auto-discovered HTTP routes.
```

The HTTP slice lives at `src/Feature/Hello/Controller/HelloController.php`
and demonstrates `#[Controller]` + `#[Route]` + JSON/HTML response helpers.

The CLI slice lives at `src/Feature/Hello/Command/HelloCommand.php` and
demonstrates `#[AsCommand]` + Symfony Console. Commands are picked up by
`Nqphp\Core\Console\CommandDiscoverer` at boot.

## Phase 2 plan

* Per-feature `services.yaml` autoloading via Symfony DI.
* Middleware pipeline (`#[Middleware]` attribute or service-tag).
* `#[Entity]` attribute + Doctrine bridge.
* `#[Schedule]` cron + Symfony Scheduler integration.
* **✅ `#[AsCommand]` CLI command auto-discovery** (shipped).
* JS-module runtime helper (`csrf()`, `fetchJson()`).
* `bin/dev` orchestration script.

## License

MIT. See [LICENSE](LICENSE).
