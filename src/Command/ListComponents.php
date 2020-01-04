<?php declare(strict_types=1);

namespace SwoftLabs\ReleaseCli\Command;

use Swoft\Console\Helper\Show;
use Toolkit\Cli\App;
use Toolkit\Cli\Color;
use function basename;
use function count;
use function implode;
use function sort;
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
  --inline    Output in one line

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
        $pattern = $libsDir . '*';
        $subDirs = glob($pattern, GLOB_ONLYDIR | GLOB_MARK);

        $total = count($subDirs);
        Color::println("Components(total: $total):", 'cyan');

        $names = [];
        foreach ($subDirs as $item) {
            $names[] = basename($item);
        }

        sort($names);

        if ($app->getBoolOpt('inline')) {
            echo implode(' ', $names), PHP_EOL;
        } else {
            echo implode("\n", $names), PHP_EOL;
        }
    }
}
