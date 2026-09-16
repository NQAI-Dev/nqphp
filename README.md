# nqphp

> Modular monolith PHP framework. Symfony components under the hood.
> Vertical Slice Architecture. JS modules per controller.

## Status: pre-alpha Phase 2

Phase 1 shipped the minimum HTTP pipeline: `#[Controller] + #[Route]`
auto-discovery, HTTP kernel, front controller, `bin/console routes:list`.
Phase 2 adds CLI command auto-discovery via `#[AsCommand]` — commands in
`src/Feature/{Name}/Command/` and `src/Core/Command/` are registered
automatically by `bin/console`, no hand-wiring needed.

Phase 2 also ships the JS runtime helper: a framework-bundled
`nqphp-runtime.js` exposing `csrf()` and `fetchJson()`, served at
`/_nqphp/js/nqphp-runtime.js`. Per-feature modules under
`src/Feature/{Name}/Resources/client.js` are served at
`/_nqphp/js/{Name}/client.js`. State-changing requests are guarded by
double-submit-cookie CSRF (`X-CSRF-Token` header must equal the
`nqphp_csrf` cookie).

## Architecture decisions

* **Vertical Slice Architecture.** Each feature is a directory under
  `src/Feature/{Name}/` containing its own `Controller/`, `Entity/`,
  `Job/`, `Command/`, `Resources/`, and `config/services.yaml`. A feature
  is the unit of isolation and (eventually) extraction. Public JavaScript
  for the feature lives under `Resources/` and is auto-served as ES
  modules at `/_nqphp/js/{Name}/{file}.js`.
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
* **✅ JS-module runtime helper** (`csrf()`, `fetchJson()`, `loadModule()`)
  — double-submit-cookie CSRF, per-feature modules under
  `src/Feature/{Name}/Resources/`, served at `/_nqphp/js/{Name}/{file}.js`.
* `bin/dev` orchestration script.

### Using the JS runtime from a feature

From any controller's `render()` body, drop the framework runtime as a
module and (optionally) your feature's own module:

```html
<script type="module" src="/_nqphp/js/nqphp-runtime.js"></script>
<script type="module" src="/_nqphp/js/Hello/client.js"></script>
```

In your feature's `client.js`:

```js
import { csrf, fetchJson } from '/_nqphp/js/nqphp-runtime.js';

const data = await fetchJson('/api/save', {
  method: 'POST',
  json: { hello: 'world' },
});
// `fetchJson` automatically attaches `X-CSRF-Token: <csrf()>`
// for POST/PUT/PATCH/DELETE — controllers don't have to think about it.
```

For state-changing endpoints you don't need any PHP changes: the
`Kernel` enforces CSRF before dispatch and rejects mismatched or
missing tokens with 403.

## License

MIT. See [LICENSE](LICENSE).
