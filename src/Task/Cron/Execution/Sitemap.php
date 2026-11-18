<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Task\Cron\Execution;

use Infrangible\Sitemap\Traits\Task;
use Infrangible\Task\Cron\Execution\Base;

/**
 * Backend model of the cron expression of the sitemap task in the configuration.
 */
class Sitemap extends Base
{
    use Task;
}
