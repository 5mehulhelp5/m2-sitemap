<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Traits;

use Infrangible\Sitemap\Task\Sitemap;

/**
 * Name and class of the sitemap task, for its controllers, cron jobs and console command.
 */
trait Task
{
    /**
     * Returns the name of the task.
     *
     * @return string
     */
    protected function getTaskName(): string
    {
        return 'sitemap';
    }

    /**
     * Returns the class of the task.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return Sitemap::class;
    }
}
