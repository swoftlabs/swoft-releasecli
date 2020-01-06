<?php declare(strict_types=1);

namespace SwoftLabs\ReleaseCli\Command;

use Swoft\Console\Helper\Interact;
use Swoft\Console\Helper\Show;
use Swoft\Stdlib\Helper\Sys;
use SwoftLabs\ReleaseCli\CoScheduler;
use Swoole\Coroutine;
use Toolkit\Cli\App;
use Toolkit\Cli\Color;
use function count;
use function sprintf;

/**
 * Class GitReleaseTag
 *
 * @package SwoftLabs\ReleaseCli\Command
 */
class GitReleaseTag extends BaseCommand
{
    /**
     * @var string
     */
    private $tmpDir;

    /**
     * @var array
     */
    private $result = [];

    public function getHelpConfig(): array
    {
        $help = <<<STR
Arguments:
  names   The component names. If name equals 'component', operate for the main project.

Options:
  --all                 Apply for all components
  --debug               Open debug mode
  -t, --tag <version>   The tag version. eg: v2.0.2
  -y, --yes             No confirmation required

Example:
  {{fullCmd}} -t v2.0.3 --all
  {{fullCmd}} -t v2.0.3 event
  {{fullCmd}} -t v2.0.3 event config

STR;

        //   --recopy              Recopy components codes to tmp dir for operation
        return [
            'name'  => 'tag:release',
            'desc'  => 'Release all sub-repo to new tag version and push to remote repo',
            'usage' => 'tag:release [options] [arguments]',
            'help'  => $help,
        ];
    }

    public function __invoke(App $app)
    {
        $newTag = $app->getStrOpt('tag', $app->getStrOpt('t'));
        if (!$newTag) {
            Color::println('Please input an new tag for release. eg: v2.0.4', 'error');
            return;
        }

        // operate the component project
        if ($app->getArg(0) === self::MAIN) {
            self::doTagAndPush('component', $newTag, $this->repoDir);
            Color::println("\nRelease Tag({$newTag}) Complete", 'cyan');
            return;
        }

        $this->tmpDir = '/tmp/sub-repos';
        $this->debug  = $app->getBoolOpt('debug');
        $debugText    = $this->debug ? 'True' : 'False';

        Color::println("Will release new tag: $newTag (DEBUG: $debugText)");

        $yes = $app->getBoolOpt('yes', $app->getBoolOpt('y'));
        if (!$yes && Interact::unConfirm('Now, continue')) {
            Color::println('Bye Bye');
            return;
        }

        // $targetBranch = 'master';
        $makeTmpDir = "rm -rf {$this->tmpDir} && mkdir {$this->tmpDir}";

        Color::println("> $makeTmpDir", 'yellow');
        [$code, $msg,] = Sys::run($makeTmpDir);
        if ($code !== 0) {
            Color::println('[ERROR]' . $msg, 'error');
            return;
        }

        $finder = new GitFindTag();
        $runner = CoScheduler::new();

        foreach ($this->findComponents($app) as $dir) {
            $this->releaseTag($runner, $finder, basename($dir), $newTag);
        }

        $runner->start();

        $total  = count($this->result);
        $result = $this->result;
        Color::println("\nRelease Tag({$newTag}) Complete(total: $total)", 'cyan');

        ksort($result);
        Show::aList($result);
    }

    /**
     * @param CoScheduler  $runner
     * @param GitFindTag $finder
     * @param string     $name
     * @param string     $newTag
     */
    private function releaseTag(CoScheduler $runner, GitFindTag $finder, string $name, string $newTag): void
    {
        $tmpDir  = $this->tmpDir;
        $repoDir = $tmpDir . '/' . $name;

        // - ensure no repo dir
        $rmRepoDir = "rm -rf $repoDir";
        Color::println("> $rmRepoDir", 'yellow');

        [$code, $msg,] = Sys::run($rmRepoDir);
        if ($code !== 0) {
            $msg = "Remove repo dir fail of the {$name}. Output: {$msg}";
            Color::println($msg, 'error');
            return;
        }

        // $remoteTpl = 'https://github.com/swoft-cloud/swoft-%s.git';
        $remoteTpl = 'git@github.com:swoft-cloud/swoft-%s.git';
        $remoteUrl = sprintf($remoteTpl, $name);

        // - clone remote repo
        $cloneCmd = "cd {$tmpDir} && git clone {$remoteUrl} $name";
        Color::println("> $cloneCmd", 'yellow');

        if (!$this->debug) {
            [$code, $msg,] = Sys::run($cloneCmd, $tmpDir);

            if ($code !== 0) {
                $msg = "Clone repo fail of the {$name}. Output: {$msg}";
                Color::println($msg, 'error');
                return;
            }
        }

        // - check last tag
        Color::println("------ Check last tag for thr component: $name");

        $lastTag = $finder->findTag($repoDir);
        if ($lastTag === $newTag) {
            Color::println("The component '{$name}' has been exists tag: {$newTag}, skip release");
            return;
        }

        $runner->add(function () use ($name, $newTag, $repoDir) {
            $ok = self::doTagAndPush($name, $newTag, $repoDir);

            // Save result status
            $this->result[$name] = $ok ? 'OK' : 'Fail';
            Color::println("- Complete for {$name}\n", 'cyan');
        });
    }

    /**
     * @param string $name
     * @param string $newTag
     * @param string $repoDir
     *
     * @return bool
     */
    private static function doTagAndPush(string $name, string $newTag, string $repoDir): bool
    {
        $addTagCmd = "cd {$repoDir} && git tag -a {$newTag} -m \"Release {$newTag}\"";

        Color::println("====== Release the component:【{$name}】");
        Color::println("> $addTagCmd", 'yellow');

        // - add new tag
        $ret = Coroutine::exec($addTagCmd);
        if ((int)$ret['code'] !== 0) {
            $msg = "Add tag fail of the {$name}. Output: {$ret['output']}";
            Color::println($msg, 'error');
            return false;
        }

        $pushTagCmd = "cd {$repoDir} && git push origin {$newTag}";
        Color::println("> $pushTagCmd", 'yellow');

        // - push new tag
        $ret = Coroutine::exec($pushTagCmd);
        if ((int)$ret['code'] !== 0) {
            $msg = "Push tag fail of the {$name}. Output: {$ret['output']}";
            Color::println($msg, 'error');
            return false;
        }

        return true;
    }
}
