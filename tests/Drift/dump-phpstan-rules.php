<?php

declare(strict_types=1);

// Prints the rule classes PHPStan registers for the ruleset at level max, one per line.
// It runs in its own process: resolving the rules boots Larastan's Laravel application.
use PhpParser\Node;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\ExtensionInstaller\GeneratedConfig;
use PHPStan\Rules\Rule;

$rootDirectory = dirname(__DIR__, 2);
require "{$rootDirectory}/vendor/autoload.php";

/** @var array{require: array<string, string>} $composer */
$composer = json_decode((string)file_get_contents("{$rootDirectory}/composer.json"), true, flags: JSON_THROW_ON_ERROR);

// extension-installer also loads the dev-only extensions, which users of the ruleset don't get.
// GeneratedConfig is how the analyse command finds the installed extensions too.
// @phpstan-ignore phpstanApi.classConstant
$extensionFiles = collect(GeneratedConfig::EXTENSIONS)
    ->filter(fn(array $extension, string $package): bool => isset($composer['require'][$package]))
    ->flatMap(fn(array $extension): array => collect($extension['extra']['includes'])
        ->map(fn(string $include): string => "{$extension['install_path']}/{$include}")
        ->all())
    ->values()
    ->all();

// The analyse command adds the level's config, so it is added by hand here.
$levelConfigFile = "phar://{$rootDirectory}/vendor/phpstan/phpstan/phpstan.phar/conf/config.levelmax.neon";

$container = (new ContainerFactory($rootDirectory))->create(
    "{$rootDirectory}/build/phpstan-drift",
    [...$extensionFiles, __DIR__ . '/config/phpstan.neon', $levelConfigFile],
    ["{$rootDirectory}/src"],
);

/** @var list<string> $bootstrapFiles */
$bootstrapFiles = $container->getParameter('bootstrapFiles');
foreach ($bootstrapFiles as $bootstrapFile) {
    (static function (string $file): void {
        require_once $file;
    })($bootstrapFile);
}

/** @var list<Rule<Node>> $rules */
$rules = $container->getServicesByTag('phpstan.rules.rule');

echo collect($rules)
    ->map(fn(Rule $rule): string => $rule::class)
    ->unique()
    ->sort()
    ->implode("\n"), "\n";
