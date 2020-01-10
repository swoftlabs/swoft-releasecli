<?php declare(strict_types=1);

namespace SwoftLabs\ReleaseCli\Command;

use Swoft\Console\Helper\Show;
use SwoftLabs\ReleaseCli\CoScheduler;
use SwoftLabs\ReleaseCli\ProcessPool;
use SwoftLabs\ReleaseCli\ProcessPool2;
use Swoole\Atomic;
use Swoole\Coroutine;
use Swoole\Process\Pool;
use Swoole\Table;
use Toolkit\Cli\App;
use Toolkit\Cli\Color;
use function basename;
use function ceil;
use function count;
use function ksort;
use function sprintf;

/**
 * Class GitForcePush
 *
 * @package SwoftLabs\ReleaseCli\Command
 */
class GitForcePush extends BaseCommand
{
    protected const TPL = 'git push %s `git subtree split --prefix src/%s master`:%s --force';

    /**
     * @var array
     */
    private $subDirs;

    /**
     * @var string
     */
    private $branch;

    public function getHelpConfig(): array
    {
        $help = <<<STR
Arguments:
  names   The component names

Options:
  --all         Apply for all components
  --debug       Open debug mode
  --mode [p|c]  Run mode. allow p: multi-process c: multi-coroutine

Example:
  {{fullCmd}} --all
  {{fullCmd}} event
  {{fullCmd}} event config

STR;

        return [
            'name'  => 'git:fpush',
            'desc'  => 'Force push all update to remote sub-repo by git push with --force',
            'usage' => 'git:fpush [options] [arguments]',
            'help'  => $help,
        ];
    }

    /**
     * @param App $app
     */
    public function __invoke(App $app)
    {
        $this->branch = $targetBranch = 'master';
        $this->debug  = $app->getBoolOpt('debug');

        $subDirs = $this->subDirs = $this->allComponents($app);
        $counts  = count($subDirs);

        Color::println('update codes to latest by git pull');
        self::exec('git checkout . && git pull');

        Color::println("will handled component number: $counts");

        if ($counts === 1) {
            $name = basename($subDirs[0]);
            $cmd  = sprintf(self::TPL, $name, $name, $targetBranch);
            $ok   = $this->pushToRepo($cmd, $name);

            $result[$name] = $ok ? 'OK' : 'FAIL';
        } elseif ($app->getStrOpt('mode', 'p') === 'p') {
            $result = $this->processRun($subDirs);
        } else {
            $result = $this->coroutineRun($subDirs);
        }

        Color::println("\nForce Push Complete(total: $counts)", 'cyan');
        if ($result) {
            ksort($result);
            Show::aList($result);
        }
    }

    protected function coroutineRun(array $subDirs): array
    {
        $result = [];
        $runner = CoScheduler::new();
        $branch = $this->branch;

        // force push:
        // git push tcp-server `git subtree split --prefix src/tcp-server master`:master --force
        foreach ($subDirs as $dir) {
            $name = basename($dir);
            // 先分割，在强推上去
            $cmd = "git push {$name} `git subtree split --prefix src/{$name} master`:{$branch} --force";

            $runner->add(function () use ($name, $cmd, &$result) {
                $result[$name] = $this->pushToRepo($cmd, $name) ? 'OK' : 'FAIL';

                Coroutine::sleep(1);
            });
        }

        $runner->start();

        return $result;
    }

    /**
     * @param array  $subDirs
     *
     * @return array
     */
    protected function processRun(array $subDirs): array
    {
        $workNum = (int)ceil($this->cpuNum * 1.5);
        $allNum  = count($subDirs);

        if ($allNum <= $workNum) {
            $workNum = $allNum;
        }

        Color::println("will started process number: $workNum");

        $results = [];
        $atomic  = new Atomic($allNum);

        $table = new Table(48);
        $table->column('name', Table::TYPE_STRING, 16);
        $table->column('value', Table::TYPE_STRING, 8);
        $table->create();

        $this->useCustomPool($workNum, $table, $atomic);
        // $this->useSwoolePool($workNum, $table, $atomic);

        /** @var Table\Row $row */
        foreach ($table as $row) {
            $results[$row['name']] = $row['value'];
        }

        $table->destroy();

        return $results;
    }

    protected function useCustomPool(int $workNum, Table $table, Atomic $atomic): void
    {
        $pool = ProcessPool2::new($workNum);
        $pool->onStart(function (ProcessPool2 $pool, int $workerId) use ($table, $atomic) {
            $targetBranch = $this->branch;
            // force push:
            // git push tcp-server `git subtree split --prefix src/tcp-server master`:master --force
            while ($num = $atomic->get()) {
                $index = $atomic->sub(1);

                $dir  = $this->subDirs[$index];
                $name = basename($dir);

                // 先分割，在强推上去
                $cmd = sprintf(self::TPL, $name, $name, $targetBranch);
                $ok  = $this->pushToRepo($cmd, $name);

                $table->set($name, [
                    'name'  => $name,
                    'value' => $ok ? 'OK' : 'FAIL',
                ]);
            }
        });

        $pool->start();
    }

    protected function useSwoolePool(int $workNum, Table $table, Atomic $atomic): void
    {
        $pool = ProcessPool::new($workNum);
        $pool->onStart(function (Pool $pool, int $workerId) use ($table, $atomic) {
            $targetBranch = $this->branch;
            // force push:
            // git push tcp-server `git subtree split --prefix src/tcp-server master`:master --force
            while ($num = $atomic->get()) {
                $index = $atomic->sub(1);

                $dir  = $this->subDirs[$index];
                $name = basename($dir);
                // 先分割，在强推上去
                $cmd = sprintf(self::TPL, $name, $name, $targetBranch);
                $ok  = $this->pushToRepo($cmd, $name);

                $table->set($name, [
                    'name'  => $name,
                    'value' => $ok ? 'OK' : 'FAIL',
                ]);
            }

            // SIGTERM=15
            // Process::kill(\getmypid(), 15);
            // Coroutine::sleep(3);
        });

        $pool->start();
    }

    /**
     * @param string $cmd
     * @param string $name
     *
     * @return bool
     */
    protected function pushToRepo(string $cmd, string $name): bool
    {
        Color::println("\n====== Push the component:【{$name}】");
        Color::println("> $cmd", 'yellow');

        $ret = Coroutine::exec($cmd);
        if ((int)$ret['code'] !== 0) {
            $msg = "Push to remote fail of the {$name}. Output: {$ret['output']}";
            Color::println($msg, 'error');
            return false;
        }

        Color::println("- Complete for {$name}\n", 'cyan');
        return true;
    }
}
