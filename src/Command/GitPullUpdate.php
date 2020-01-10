<?php declare(strict_types=1);

namespace SwoftLabs\ReleaseCli\Command;

use Toolkit\Cli\App;
use Toolkit\Cli\Color;

/**
 * Class GitPullUpdate
 */
class GitPullUpdate extends BaseCommand
{
    public function getHelpConfig(): array
    {
        $help = <<<STR
Example:
  {{command}}

STR;

        return [
            'name' => 'git:up',
            'desc' => 'update all codes to latest by git pull',
            'help' => $help,
        ];
    }

    /**
     * @param App $app
     */
    public function __invoke(App $app): void
    {
        Color::println('Update to latest:');

        $cmd = 'git checkout . && git pull';
        $ret = self::exec($cmd);

        echo $ret['output'];

        Color::println('Complete');
    }
}
