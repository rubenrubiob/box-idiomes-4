<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;
use Webmozart\Glob\Glob;

return static function (Configuration $config): Configuration {
    return $config
        ->setAdditionalFilesFor('flux/box-idiomes', [
            __FILE__,
            ...Glob::glob(__DIR__ . '/var/cache/dev/Container*/*.php'),
            ...Glob::glob(__DIR__ . '/config/**/*.php'),
            ...Glob::glob(__DIR__ . '/bin/*.php'),
            ...Glob::glob(__DIR__ . '/public/*.php'),
        ])
        ->addNamedFilter(NamedFilter::fromString('symfony/runtime'))
    ;
};
