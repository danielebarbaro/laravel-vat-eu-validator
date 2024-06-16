<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withSets([
        LaravelSetList::LARAVEL_100,
        LaravelSetList::LARAVEL_110,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,

        SetList::PHP_83,
        SetList::PHP_82,
        LevelSetList::UP_TO_PHP_82,

        SetList::CODING_STYLE,
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,

    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ]);
