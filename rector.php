<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Php70\Rector\MethodCall\ThisCallOnStaticMethodToStaticCallRector;
use Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $loader): void {
    $rules = $loader->services();
    $options = $loader->parameters();

    # Options
    $options->set(Option::PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);
    $options->set(Option::AUTO_IMPORT_NAMES, true);

    # PHP Rules
    $loader->import(LevelSetList::UP_TO_PHP_74);

    $rules->remove(AddLiteralSeparatorToNumberRector::class);
    $rules->remove(ThisCallOnStaticMethodToStaticCallRector::class);

    $rules->set(TypedPropertyRector::class)->configure([
        TypedPropertyRector::INLINE_PUBLIC => true
    ]);
};
