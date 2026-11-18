<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Console\Command\Script;

use Infrangible\Task\Console\Command\Script\Task;

/**
 * Runs the sitemap task on the console.
 */
class Sitemap extends Task
{
    use \Infrangible\Sitemap\Traits\Task;
}
