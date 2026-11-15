# PHPStan ruleset

`phpstan-rules.neon` reports the rules of the
[laravel-pro-skills](https://github.com/adamczykpiotr/laravel-pro-skills) standard that a formatter
can't fix safely. It reports them and leaves the fix to you.

Every rule has its own identifier. To turn a rule off everywhere, set its identifier to `false` under
`phpstan-rules` in `config/ultimate-formatter.php` (see [Ruleset flags](../README.md#ruleset-flags)).
For deliberate exceptions in some paths, target the identifier with a baseline or an `ignoreErrors`
entry. For example, controllers whose response shape outside consumers depend on can keep
`response()->json()`:

```neon
parameters:
    ignoreErrors:
        -
            identifier: ultimateFormatter.disallowResponseJson
            paths:
                - app/Http/Controllers/Integrations/*
```

Paths are relative to the config file, and `*` matches subdirectories too.

## Banned calls

Via [phpstan-disallowed-calls](https://github.com/spaze/phpstan-disallowed-calls), plus one custom rule
for `Log::shouldReceive()`.

| Banned                                                                                                               | Use instead                           | Identifier                                                   |
|----------------------------------------------------------------------------------------------------------------------|---------------------------------------|--------------------------------------------------------------|
| `sprintf()`                                                                                                          | Interpolated named variables          | `ultimateFormatter.disallowSprintf`                          |
| `array_*()`                                                                                                          | A Collection pipeline                 | `ultimateFormatter.disallowNativeArrayFunctions`             |
| `method_exists()`                                                                                                    | An interface and `instanceof`         | `ultimateFormatter.disallowMethodExists`                     |
| `file_exists()`                                                                                                      | `File::exists()`                      | `ultimateFormatter.disallowFileExists`                       |
| `preg_*()`                                                                                                           | `Str` / `Stringable`                  | `ultimateFormatter.disallowNativePregFunctions`              |
| `auth()`, `back()`, `cookie()`, `redirect()`, `request()`, `response()`, `session()`, `to_route()` in `App\Services` | Pass the value in as an argument      | `ultimateFormatter.disallowRequestResponseHelpersInServices` |
| `Model::create()`, `firstOrCreate()`, `updateOrCreate()`, `->fill()`, `->update()`                                   | `new`, assign each property, `save()` | `ultimateFormatter.disallowModelMassAssignment`              |
| `response()->json()`                                                                                                 | `Resp::success()` / `Resp::error()`   | `ultimateFormatter.disallowResponseJson`                     |
| `app()->environment()`, `App::environment()`                                                                         | A config flag                         | `ultimateFormatter.disallowAppEnvironment`                   |
| `Log::shouldReceive()`                                                                                               | `Log::spy()`                          | `ultimateFormatter.disallowLogShouldReceive`                 |

Larastan's `noEnvCallsOutsideOfConfig` stays on, so `env()` is only called in `config/`
(`larastan.noEnvCallsOutsideOfConfig`).

## Custom rules

The invariants that neither disallowed-calls nor phpat can express, one class each in
[`src/PhpStan/Rules`](../src/PhpStan/Rules).

### Migrations

These run inside classes that extend `Illuminate\Database\Migrations\Migration`. Larastan reads the
schema from the migrations to type the model properties, and it skips schema calls whose names aren't
string literals, so a dropped column would stay typed as present. The closure rule is what lets the
others work: PHPStan only knows `$table` is a `Blueprint` when the parameter says so.

| Rule                                                                                                                   | Identifier                                        |
|------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------|
| No column is defined after `$table->timestamps()` (or `timestampsTz()`, `nullableTimestamps()`, `nullableTimestampsTz()`) in the same closure. Indexes and foreign keys may follow. | `ultimateFormatter.migrationTimestampsLast`       |
| Every migration has both `up()` and `down()`.                                                                          | `ultimateFormatter.migrationUpAndDown`            |
| `Schema::` calls, the first argument of `$table->` calls (every argument of `drop*()` and `rename*()`, none of `comment()`, `charset()`, `collation()` and `engine()`), and `->constrained()`, `->references()`, `->on()` and `->after()` name tables and columns with string literals or literal arrays: no variables, properties, constants, method calls or loop variables. | `ultimateFormatter.migrationLiteralSchemaNames`   |
| Closures of `Schema::create()`, `table()` and `whenTable*()` are `function (Blueprint $table): void`, never `fn`.                                                   | `ultimateFormatter.migrationSchemaClosure`        |
| Foreign keys use `foreignId(...)->constrained()`, not `unsignedBigInteger('*_id')` or `unsignedInteger('*_id')`.        | `ultimateFormatter.migrationForeignIdConstrained` |

### Resources, enums, queued classes and models

| Rule                                                                                                                   | Identifier                                         |
|------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------|
| String keys of the arrays a `JsonResource::toArray()` returns are camelCase. That includes nested arrays, arrays passed to `$this->merge()`, `mergeWhen()`, `mergeUnless()`, `when()` and `unless()`, and arrays returned from closures inside `toArray()`. One leading underscore marks a meta key, such as `_actions`, and is allowed. | `ultimateFormatter.resourceKeysCamelCase`          |
| Cases of string-backed enums are `UPPER_SNAKE_CASE`.                                                                   | `ultimateFormatter.enumCasesUpperSnakeCase`        |
| Their backing values are `UPPER_SNAKE_CASE` and equal the case name (`ACTIVE = 'ACTIVE'`). Both rules skip int-backed and pure enums, and `External{Name}` enums, which mirror values from outside the app. | `ultimateFormatter.enumValuesMatchCaseNames`       |
| Classes implementing `ShouldQueue` (jobs, queued listeners, mailables, notifications) declare no `private` properties, promoted ones included. `SerializesModels` reflects over the concrete class, so a private property on a parent arrives empty on the worker. | `ultimateFormatter.queuedClassNoPrivateProperties` |
| Models don't declare `$with`. Eager-load per query with `with()`.                                                       | `ultimateFormatter.modelNoWith`                    |
| Models don't declare `$appends`. Shape the output in a `JsonResource`.                                                  | `ultimateFormatter.modelNoAppends`                 |

## Architecture

Via [phpat](https://github.com/carlosas/phpat). The rules are grouped by area, one class each, in
[`src/PhpStan/Architecture`](../src/PhpStan/Architecture). The identifier is `phpat.` followed by the
method name, such as `phpat.testServicesDoNotDependOnRequestResponse`.

### Controllers ([`ControllerRules`](../src/PhpStan/Architecture/ControllerRules.php))

- `testControllersAreNamedController`: concrete classes in `App\Http\Controllers` are named `{Name}Controller`.

### Services ([`ServiceRules`](../src/PhpStan/Architecture/ServiceRules.php))

- `testServicesAreNamedService`: concrete classes in `App\Services` are named `{Name}Service`. Off by
  default, since `App\Services` often holds mappers and similar classes.
- `testServicesAreFinal`: concrete classes in `App\Services` are `final`, except `Base{Name}` classes,
  which are meant to be extended. Services are composed, not extended.
- `testServicesAreReadonly`: classes in `App\Services` are `readonly`, so they can't hold state between
  calls. That includes `Base{Name}` and abstract classes, since a readonly class can only extend a readonly
  parent.
- `testServicesDoNotDependOnRequestResponse`: services don't depend on `Illuminate\Http` (the HTTP
  client, `Illuminate\Http\Client`, is allowed), the session, the auth guard (`AuthManager`,
  `SessionGuard` and the `Factory`, `Guard` and `StatefulGuard` contracts), or the `Auth`, `Cookie`,
  `Redirect`, `Request`, `Response` and `Session` facades. A service behaves the same whether a
  controller, a job, a command or a test calls it. Pass the user in instead, typed as the model or as
  `Authenticatable`. For a login flow such as OIDC, the service builds the authorization URL, exchanges
  the code and resolves the user; the controller keeps the state in the session, logs the user in and
  redirects.

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
