# RFC 0001: Architecture and Quality Gates

## Status

Accepted

## Context

The project started as a compact Laravel implementation of a test assignment, but it already contains several independent concerns:

- scheduled joke import from an external API;
- public JSON endpoints;
- visit tracking and statistics aggregation;
- protected statistics UI;
- browser-only utility scripts.

Keeping all persistence details directly inside controllers or framework models would make the code harder to test and easier to couple to Laravel-specific APIs. The project also needs a repeatable quality workflow that can be run locally and in CI.

## Decision

Use a layered architecture with explicit dependency direction:

- `Domain` contains entities, value objects and ports.
- `Application` contains CQRS use cases under `Command` and `Query`.
- `Infrastructure` implements ports and talks to HTTP clients, Redis and persistence.
- `app` remains the Laravel delivery layer: controllers, middleware, providers and Eloquent models.

Eloquent models live in `app/Models`. Infrastructure repositories do not import `App\Models` directly. They resolve model classes through `Infrastructure\Persistence\ModelResolver`, backed by `config/persistence.php`.

Eloquent casts are not used for domain conversion. Domain value objects are created explicitly in mapper classes. This keeps `Domain` free from Laravel cast contracts and framework date types.

Statistics authentication uses JWT instead of HTTP Basic auth. The browser flow stores the token in an HttpOnly cookie, and API-style requests may use `Authorization: Bearer`.

Quality gates:

- PHPUnit for behavior.
- Pint for formatting.
- PHPStan configured at level 8.
- Psalm configured at level 1.
- `Makefile` provides the canonical local commands.

## Consequences

Positive:

- Domain and application code remain independent from Laravel.
- Repository queries are reusable through Criteria objects.
- Authentication is explicit and extensible.
- Quality checks are discoverable and consistent.

Tradeoffs:

- Some Laravel dynamic behavior needs narrow static-analysis suppressions at framework boundaries.
- The model resolver adds indirection, but it avoids coupling Infrastructure to Laravel model namespaces.
- Static analysis scope excludes Laravel-generated DSL-heavy files where framework magic dominates signal.

## Validation

The accepted validation path is:

```bash
make format
make test
make psalm
make stan
make build
```

For full pre-merge verification:

```bash
make quality
```
