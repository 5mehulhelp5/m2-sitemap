<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Task\Cron;

use Infrangible\Sitemap\Traits\Task;
use Infrangible\Task\Cron\Base;

/**
 * The cron job task_sitemap, which generates the sitemaps.
 */
class Sitemap extends Base
{
    use Task;
}
