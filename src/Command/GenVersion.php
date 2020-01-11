<?php declare(strict_types=1);

namespace SwoftLabs\ReleaseCli\Command;

use Toolkit\Cli\App;
use Toolkit\Cli\Color;
use function basename;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_replace;

/**
 * Class GenVersion
 *
 * @package SwoftLabs\ReleaseCli\Command
 */
class GenVersion extends BaseCommand
{
    public const ADD_POSITION  = '"type": "library",';
    public const MATCH_VERSION = '/"version": "([\w.]+)"/';

    /**
     * @var string
     */
    private $version;

    /**
     * @var int
     */
    private $updated = 0;

    public function getHelpConfig(): array
    {
        $help = <<<STR
Arguments:
  names   The component names

Options:
  --all     Apply for all components
  -c        Commit to git after update by `git commit`
  -v        The want added version. eg: v2.0.3

Example:
  {{command}} -v v2.0.3 --all
  {{command}} -v v2.0.3 http-server
  {{command}} -v v2.0.3 http-server http-message

STR;

        return [
            'name'  => 'gen:version',
            'desc'  => 'generate an version info to composer.json',
            'usage' => 'gen:version NAME(s)',
            'help'  => $help,
        ];
    }

    public function __invoke(App $app): void
    {
        if (!$version = $app->getStrOpt('v')) {
            echo Color::render("Please input an version by option: -v\n", 'error');
            return;
        }

        $this->version = 'v' . trim($version, 'v');

        echo Color::render("Input new version is: {$this->version}\n", 'info');

        foreach ($this->findComponents($app) as $dir) {
            $this->addVersionToComposer($dir . 'composer.json', basename($dir));
        }

        if ($this->updated > 0 && $app->getBoolOpt('c')) {
            self::gitCommit("update: add {$this->version} for package composer.json");
        }

        echo Color::render("Complete\n", 'cyan');
    }

    /**
     * @param string $file
     * @param string $name
     */
    private function addVersionToComposer(string $file, string $name = ''): void
    {
        $count = 0;
        $name  = $name ?: basename(dirname($file));

        $content = file_get_contents($file);
        $replace = sprintf('"version": "%s"', $this->version);

        preg_match(self::MATCH_VERSION, $content, $matches);

        // Not found, is first add.
        if (!isset($matches[1])) {
            $replace = self::ADD_POSITION . "\n  {$replace},";
            $content = str_replace(self::ADD_POSITION, $replace, $content, $count);
        } else {
            if ($matches[1] === $this->version) {
                Color::println("Version is no changed, skip: $name");
                return;
            }

            $content = preg_replace(self::MATCH_VERSION, $replace, $content, 1, $count);
        }

        if (0 === $count) {
            Color::println("Failed add version for component: $name\n", 'error');
            return;
        }

        $this->updated++;

        Color::println("Append version for the component: $name\n", 'info');

        file_put_contents($file, $content);
    }
}
