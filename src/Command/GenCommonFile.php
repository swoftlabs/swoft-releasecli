<?php declare(strict_types=1);

namespace SwoftLabs\ReleaseCli\Command;

use Swoft\Stdlib\Helper\Dir;
use Toolkit\Cli\App;
use Toolkit\Cli\Color;
use function array_values;
use function basename;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function str_replace;
use function strtr;
use function trim;
use function ucwords;
use const SWOOLE_VERSION;

/**
 * Class GenCommonFile
 *
 * @package SwoftLabs\ReleaseCli\Command
 */
class GenCommonFile extends BaseCommand
{
    /**
     * @var string
     */
    private $tplFile;

    /**
     * @var array
     */
    private $tplFiles = [
        'README.tpl.md'       => 'README.md',
        'README.zh-CN.tpl.md' => 'README.zh-CN.md',
        'pull-request.tpl.md' => '.github/PULL_REQUEST_TEMPLATE.md',
        'issue-config.yml'    => '.github/ISSUE_TEMPLATE/config.yml',
    ];

    /**
     * @var string
     */
    private $swVersion;

    /**
     * @var array
     */
    private $contents;

    public function getHelpConfig(): array
    {
        $files = implode("\n- ", array_values($this->tplFiles));

        $help = <<<STR
Arguments:
  names   The component names

Options:
  --all     Apply for all components
  -v        The want added swoole version, default get by SWOOLE_VERSION. eg: v4.4.1

Example:
  {{command}} --all
  {{command}} http-server
  {{command}} http-server http-message

Will generate there are files:

- {$files}

STR;

        return [
            'name' => 'gen:cfile',
            'desc' => 'generate common files(eg README) for swoft component(s)',
            'help' => $help,
        ];
    }

    /**
     * @param App $app
     */
    public function __invoke(App $app): void
    {
        $tplDir = $this->baseDir . '/template/';

        $defVersion = '';
        if (defined('SWOOLE_VERSION')) {
            $defVersion = SWOOLE_VERSION;
        }

        if (!$version = $app->getStrOpt('v', $defVersion)) {
            echo Color::render("Please input an swoole version by option: -v\n", 'error');
            return;
        }

        foreach ($this->tplFiles as $tplFile => $targetFile) {
            $this->contents[$targetFile] = file_get_contents($tplDir . $tplFile);
        }

        Color::println('Generate common files for components');
        $this->swVersion = 'v' . trim($version, 'v');

        // do gen files
        foreach ($this->findComponents($app) as $dir) {
            $this->genCommonFile($dir);
        }

        echo Color::render("Complete\n", 'cyan');
    }

    /**
     * @param string $dir
     */
    private function genCommonFile(string $dir): void
    {
        $name = basename($dir);

        echo Color::render("- Generate files for the component: $name\n", 'info');

        $data = [
            '{{component}}'         => $name,
            '{{swoole_version}}'    => $this->swVersion,
            '{{component_up_word}}' => ucwords(str_replace('-', ' ', $name)),
        ];

        foreach ($this->contents as $file => $content) {
            $targetFile = $dir . $file;

            Dir::make(dirname($targetFile));
            file_put_contents($targetFile, strtr($content, $data));
        }
    }
}
