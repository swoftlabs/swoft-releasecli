<?php
/**
 * The is a config file for compile phar package.
 * User: Inhere
 * Date: 2018/1/26 22:11
 * @var \Swoft\Console\Advanced\PharCompiler $compiler
 */

// config
$compiler
    ->stripComments(true)
    ->setShebang(true)
    ->addSuffix(['.tpl', '.json', '.stub', '.yml'])
    ->addExcludeDir([
        'test',
        'doc',
        'docs',
        'tests',
        'Tests',
        'example',
        'runtime',
        'swoole-ide-helper',
    ])
    ->in(['./', './template'])
    ->addFile([
        // 'LICENSE',
        'composer.json',
        'README.md',
    ])
    ->setCliIndex('bin/releasecli')
    // ->setWebIndex('web/index.php')
    // ->setVersionFile('config/config.php')
;

// $stripDirs = [
//     'psr/',
//     'nikic/',
//     'monolog/',
//     'symfony/',
//     'toolkit/',
// ];
