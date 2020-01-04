<?php declare(strict_types=1);

namespace SwoftLabs\ReleaseCli\Command;

use Toolkit\Cli\App;
use Toolkit\Cli\Color;
use function basename;
use const GLOB_MARK;
use const GLOB_ONLYDIR;
use const PHP_EOL;

/**
 * Class ListComponents
 */
class ListComponents
{
    public function getHelpConfig(): array
    {
        $help = <<<STR
Options:
  --info    Output some information

Example:
  {{fullCmd}}

STR;

        return [
            'name'  => 'list',
            'desc'  => 'list all swoft components in src/ dir',
            'help'  => $help,
        ];
    }

    public function __invoke(App $app): void
    {
        $libsDir = $app->getPwd() . '/src/';

        Color::println('Components:', 'cyan');
        $flags   = GLOB_ONLYDIR | GLOB_MARK;
        $pattern = $libsDir . '*';

        foreach (glob($pattern, $flags) as $item) {
            echo basename($item), PHP_EOL;
        }
    }
}
