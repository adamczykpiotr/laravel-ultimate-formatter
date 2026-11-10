# PHPStan ruleset

`phpstan-rules.neon` reports the rules of the
[laravel-pro-skills](https://github.com/adamczykpiotr/laravel-pro-skills) standard that a formatter
can't fix safely. It reports them and leaves the fix to you.

Every rule has its own identifier, so a baseline or an `ignoreErrors` entry can target one rule.
Use that for deliberate exceptions. For example, controllers whose response shape outside consumers
depend on can keep `response()->json()`:

```neon
parameters:
    ignoreErrors:
        -
            identifier: ultimateFormatter.responseJson
            paths:
                - app/Http/Controllers/Integrations/*
```

Paths are relative to the config file, and `*` matches subdirectories too.

## Banned calls

Via [phpstan-disallowed-calls](https://github.com/spaze/phpstan-disallowed-calls), plus one custom rule
for `Log::shouldReceive()`.

| Banned                                                                                                     | Use instead                          | Identifier                              |
|------------------------------------------------------------------------------------------------------------|--------------------------------------|-----------------------------------------|
| `sprintf()`                                                                                                | Interpolated named variables         | `ultimateFormatter.sprintf`             |
| `array_*()`                                                                                                | A Collection pipeline                | `ultimateFormatter.arrayFunction`       |
| `method_exists()`                                                                                          | An interface and `instanceof`        | `ultimateFormatter.methodExists`        |
| `file_exists()`                                                                                            | `File::exists()`                     | `ultimateFormatter.fileExists`          |
| `preg_*()`                                                                                                 | `Str` / `Stringable`                 | `ultimateFormatter.pregFunction`        |
| `auth()`, `back()`, `cookie()`, `redirect()`, `request()`, `response()`, `session()`, `to_route()` in `App\Services` | Pass the value in as an argument     | `ultimateFormatter.httpInService`       |
| `Model::create()`, `firstOrCreate()`, `updateOrCreate()`, `->fill()`, `->update()`                         | `new`, assign each property, `save()` | `ultimateFormatter.modelAttributeArray` |
| `response()->json()`                                                                                       | `Resp::success()` / `Resp::error()`  | `ultimateFormatter.responseJson`        |
| `app()->environment()`, `App::environment()`                                                               | A config flag                        | `ultimateFormatter.appEnvironment`      |
| `Log::shouldReceive()`                                                                                     | `Log::spy()`                         | `ultimateFormatter.logShouldReceive`    |

Larastan's `noEnvCallsOutsideOfConfig` stays on, so `env()` is only called in `config/`
(`larastan.noEnvCallsOutsideOfConfig`).

## Architecture

Via [phpat](https://github.com/carlosas/phpat). The rules are grouped by area, one class each, in
[`src/PhpStan/Architecture`](../src/PhpStan/Architecture). The identifier is `phpat.` followed by the
method name, such as `phpat.testServicesDoNotDependOnHttp`.

### Controllers ([`ControllerRules`](../src/PhpStan/Architecture/ControllerRules.php))

- `testControllersAreNamedController`: concrete classes in `App\Http\Controllers` are named `{Name}Controller`.

### Services ([`ServiceRules`](../src/PhpStan/Architecture/ServiceRules.php))

- `testServicesAreFinal`: concrete classes in `App\Services` are `final`, except `Base{Name}` classes,
  which are meant to be extended. Services are composed, not extended.
- `testServicesAreReadonly`: classes in `App\Services` are `readonly`, so they can't hold state between
  calls. That includes `Base{Name}` and abstract classes, since a readonly class can only extend a readonly
  parent.
- `testServicesDoNotDependOnHttp`: services don't depend on `Illuminate\Http` (the HTTP client is
  allowed), the session, or the `Auth`, `Cookie`, `Redirect`, `Request`, `Response` and `Session`
  facades. A service behaves the same whether a controller, a job, a command or a test calls it.

Services aren't required to be named `{Name}Service` for now, since `App\Services` also holds mappers
and similar classes.

Mockery can't mock a `final` or `readonly` class, so `$this->mock(TagService::class)` fails on a
service. Add [dg/bypass-finals](https://github.com/dg/bypass-finals) as a dev dependency and register
it in `phpunit.xml`. It removes both keywords while the tests run:

```xml
<extensions>
    <bootstrap class="DG\BypassFinals\PHPUnitExtension"/>
</extensions>
```

### Resources ([`ResourceRules`](../src/PhpStan/Architecture/ResourceRules.php))

- `testResourcesAreNamedResource`: concrete classes in `App\Http\Resources` are named `{Name}Resource`.

### DTOs ([`DtoRules`](../src/PhpStan/Architecture/DtoRules.php))

- `testInputDtosAreNamedInput`: classes under `App\Dto\{Domain}\Input`, or a namespace below it such as
  `App\Dto\{Domain}\Input\Fields`, are named `{Action}Input`.
- `testInputDtosExtendBaseInput`: input DTOs extend `App\Dto\BaseInput`, directly or through an abstract
  parent. `BaseInput` merges the manual validation rules with the inferred ones.
- `testOutputDtosAreNamedDto`: other classes in `App\Dto` are named `{Name}Dto`. Casts and transformers,
  classes that implement spatie/laravel-data's `Cast` or `Transformer`, are skipped wherever they live.

### Requests ([`RequestRules`](../src/PhpStan/Architecture/RequestRules.php))

- `testNoClassExtendsFormRequest`: no class in `App` extends `FormRequest`. Input DTOs validate the
  request instead.

### Exceptions ([`ExceptionRules`](../src/PhpStan/Architecture/ExceptionRules.php))

- `testExceptionsAreNamedException`: exceptions in `App` are named `{Description}Exception`.
- `testExceptionsExtendBaseException`: they extend `App\Exceptions\Base\BaseException`, which the
  exception handler maps to an HTTP code and a `ResponseType`.

### Migrations ([`MigrationRules`](../src/PhpStan/Architecture/MigrationRules.php))

- `testMigrationsDoNotDependOnModels`: files in `database/migrations` don't depend on `App\Models`, so
  a migration keeps working after the model changes.
