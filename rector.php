<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/samples/public',
        __DIR__ . '/samples/src',
        __DIR__ . '/src',
    ])
    ->withPreparedSets(symfonyConfigs: true)
    ->withComposerBased(twig: true, doctrine: true)
    //->withPhpSets()
    //->withSymfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml')
    ->withSets([
        //SymfonySetList::SYMFONY_73,
        //SymfonySetList::SYMFONY_74,
        //SymfonySetList::SYMFONY_CODE_QUALITY,
        //SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ])
    ->withSkip([
        AddClosureVoidReturnTypeWhereNoReturnRector::class
    ])
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
;
