<?php declare(strict_types=1);

namespace SwoftLabs\ReleaseCli\Command;

use Toolkit\Cli\App;

/**
 * Class UpdateSelf
 */
class UpdateSelf extends BaseCommand
{
    public function getHelpConfig(): array
    {
        $help = <<<STR
Example:
  {{command}}

STR;

        return [
            'name' => 'upself',
            'desc' => 'update self to latest by git pull',
            'help' => $help,
        ];
    }

    /**
     * @param App $app
     */
    public function __invoke(App $app): void
    {
        $cmd = "cd {$this->baseDir} && git checkout . && git pull";

        $ret = self::exec($cmd);

        echo $ret['output'];
    }
}
