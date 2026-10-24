<?php

declare(strict_types=1);

namespace AdamczykPiotr\UltimateFormatter\Rector;

use AdamczykPiotr\UltimateFormatter\Rector\Rules\ExpandNullableDocblockTypesRector;
use AdamczykPiotr\UltimateFormatter\Rector\Rules\NormalizeMethodDocblockRector;
use Rector\Config\RectorConfig;
use Rector\Configuration\RectorConfigBuilder;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Set\LaravelSetList;

class RectorConfigFactory
{
    /**
     * @return RectorConfigBuilder
     */
    public static function create(): RectorConfigBuilder
    {
        return RectorConfig::configure()
            ->withParallel(maxNumberOfProcess: 4)
            ->withImportNames(
                importShortClasses: false,
                removeUnusedImports: true,
            )
            ->withComposerBased(laravel: true)
            ->withSets([
                SetList::TYPE_DECLARATION,
                SetList::TYPE_DECLARATION_DOCBLOCKS,
                LaravelSetList::LARAVEL_CODE_QUALITY,
                LaravelSetList::LARAVEL_COLLECTION,
                LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
            ])
            ->withRules([
                NormalizeMethodDocblockRector::class,
                ExpandNullableDocblockTypesRector::class,
            ]);
    }
}
