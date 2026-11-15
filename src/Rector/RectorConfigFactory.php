<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector;

use AdamczykPiotr\UltimateFormatter\Rector\Rules\ExpandNullableDocblockTypesRector;
use AdamczykPiotr\UltimateFormatter\Rector\Rules\NormalizeMethodDocblockRector;
use AdamczykPiotr\UltimateFormatter\Rector\Rules\SingleLineClosureToArrowFunctionRector;
use Composer\InstalledVersions;
use Rector\Config\RectorConfig;
use Rector\Configuration\RectorConfigBuilder;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Contract\Rector\RectorInterface;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Rector\FuncCall\RemoveDumpDataDeadCodeRector;
use RectorLaravel\Set\LaravelSetList;

class RectorConfigFactory
{
    /**
     * @return RectorConfigBuilder
     */
    public static function create(): RectorConfigBuilder
    {
        $builder = RectorConfig::configure()
            ->withParallel(maxNumberOfProcess: 4)
            ->withImportNames(
                importShortClasses: true,
                removeUnusedImports: true,
            )
            ->withComposerBased(laravel: true)
            ->withSets(self::sets())
            ->withRules(self::rules())
            // Migrations keep their closures: `Schema::table()` callbacks stay full closures.
            ->withSkip([
                SingleLineClosureToArrowFunctionRector::class => ['*/database/migrations/*'],
            ]);

        $laravelDataInstalled = InstalledVersions::isInstalled('spatie/laravel-data');
        foreach (self::configuredRules($laravelDataInstalled) as $rule => $configuration) {
            $builder->withConfiguredRule($rule, $configuration);
        }

        return $builder;
    }

    /**
     * `PRIVATIZATION` and `CODING_STYLE` stay off: the first makes job properties private, which breaks
     * queue serialization, and the second rewrites interpolated strings to `sprintf()`.
     *
     * @return list<string>
     */
    public static function sets(): array
    {
        return [
            SetList::TYPE_DECLARATION,
            SetList::TYPE_DECLARATION_DOCBLOCKS,
            SetList::CARBON,
            LaravelSetList::LARAVEL_CODE_QUALITY,
            LaravelSetList::LARAVEL_COLLECTION,
            LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
        ];
    }

    /**
     * @return list<class-string<RectorInterface>>
     */
    public static function rules(): array
    {
        return [
            NormalizeMethodDocblockRector::class,
            ExpandNullableDocblockTypesRector::class,
            ClassPropertyAssignToConstructorPromotionRector::class,
            ChangeSwitchToMatchRector::class,
            SingleLineClosureToArrowFunctionRector::class,
        ];
    }

    /**
     * @param bool $laravelDataInstalled
     *
     * @return array<class-string<ConfigurableRectorInterface>, list<mixed>>
     */
    public static function configuredRules(bool $laravelDataInstalled): array
    {
        $rules = [
            RemoveDumpDataDeadCodeRector::class => ['dd', 'dump', 'var_dump', 'print_r', 'ray'],
        ];

        // Input DTOs are built with `XxxInput::from($request)`, which validates them too.
        if ($laravelDataInstalled) {
            $rules[RenameMethodRector::class] = [
                new MethodCallRename('Spatie\LaravelData\Data', 'validateAndCreate', 'from'),
            ];
        }

        return $rules;
    }
}
