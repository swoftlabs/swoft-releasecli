<?php declare(strict_types=1);

namespace SwoftLabs\ReleaseCli\Command;

use Toolkit\Cli\App;

/**
 * Class GitHubInfo
 *
 * @package SwoftLabs\ReleaseCli\Command
 */
class GitHubInfo
{
    public function __invoke(App $app): void
    {
        // @see https://developer.github.com/v3/repos/releases/
        // curl https://api.github.com/repos/swoft-cloud/swoft/releases/latest
    }
}
