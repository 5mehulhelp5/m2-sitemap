<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Console\Command;

use Infrangible\Task\Console\Command\Task;

/**
 * The command task:sitemap, which generates the sitemaps.
 */
class Sitemap extends Task
{
    use \Infrangible\Sitemap\Traits\Task;

    /**
     * Returns the class of the script that runs the task.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return Script\Sitemap::class;
    }

    /**
     * Returns the description of the command.
     *
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Generate sitemap(s)';
    }
}
