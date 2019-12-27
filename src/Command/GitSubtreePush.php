<?php declare(strict_types=1);

namespace SwoftLabs\ReleaseCli\Command;

use Swoft\Console\Helper\Show;
use Swoole\Coroutine;
use Toolkit\Cli\App;
use Toolkit\Cli\Color;
use SwoftLabs\ReleaseCli\CoScheduler;

/**
 * Class GitSubtreePush
 *
 * @package SwoftLabs\ReleaseCli\Command
 */
class GitSubtreePush extends BaseCommand
{
    public function getHelpConfig(): array
    {
        $help = <<<STR
Arguments:
  names   The component names

Options:
  --all         Apply for all components
  --debug       Open debug mode

Example:
  {{fullCmd}} --all
  {{fullCmd}} event
  {{fullCmd}} event config

STR;

        return [
            'name'  => 'git:spush',
            'desc'  => 'Push all update to remote sub-repo by git subtree push',
            'usage' => 'git:spush [options] [arguments]',
            'help'  => $help,
        ];
    }

    public function __invoke(App $app)
    {
        if ($app->getCommand()) {
            Color::println('Please use git:fpush instead of the command', 'error');
            return;
        }

        $targetBranch = 'master';
        $this->debug = $app->getBoolOpt('debug');

        $result = [];
        $runner = CoScheduler::new();

        // git subtree push --prefix=src/annotation git@github.com:swoft-cloud/swoft-annotation.git master --squash
        // git subtree push --prefix=src/stdlib stdlib master
        foreach ($this->findComponents($app) as $dir) {
            $name = basename($dir);
            // push 加 --squash 是没有意义的
            // link https://stackoverflow.com/questions/20102594/git-subtree-push-squash-does-not-squash
            $cmd = "git subtree push --prefix=src/{$name} {$name} $targetBranch";

            $runner->add(function () use ($name, $cmd, &$result) {
                Color::println("\n====== Push the component:【{$name}】");
                Color::println("> $cmd", 'yellow');

                if ($this->debug) {
                    Color::println('[DEBUG] use co::sleep(2) to mock remote operation');
                    Coroutine::sleep(2);
                    return;
                }

                $ret = Coroutine::exec($cmd);
                if ((int)$ret['code'] !== 0) {
                    $msg = "Push to remote fail of the {$name}. Output: {$ret['output']}";
                    Color::println($msg, 'error');
                    $result[$name] = 'Fail';
                    return;
                }

                $result[$name] = 'OK';
                Color::println("- Complete for {$name}\n", 'cyan');
                Coroutine::sleep(1);
            });
        }

        $runner->start();
        Color::println("\nComplete", 'cyan');
        Show::aList($result);
    }
}
