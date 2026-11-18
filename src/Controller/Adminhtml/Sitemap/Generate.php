<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Controller\Adminhtml\Sitemap;

use Infrangible\Sitemap\Task\Sitemap;
use Infrangible\Sitemap\Traits\Task;
use Infrangible\Task\Controller\Adminhtml\Run;
use Infrangible\Task\Task\Base;

/**
 * Generates the sitemap of the file of the parameter "filename", with all stores that use this file.
 */
class Generate extends Run
{
    use Task;

    /**
     * Returns the task, limited to the file of the request.
     *
     * @return Base
     */
    public function getTask(): Base
    {
        $task = parent::getTask();

        if ($task instanceof Sitemap) {
            $task->setFileName($this->values->string($this->getRequest()->getParam('filename')));
        }

        return $task;
    }

    /**
     * Returns the ACL resource that admin users need to generate sitemaps.
     *
     * @return string
     */
    protected function getTaskResourceId(): string
    {
        return 'Infrangible_Sitemap::infrangible_sitemap';
    }

    /**
     * Returns to the list of sitemap files.
     *
     * @return string|null
     */
    protected function getRedirectPath(): ?string
    {
        return 'infrangible_sitemap/sitemap/index';
    }

    /**
     * Shows the result of the run as message in the list.
     *
     * @return bool
     */
    protected function isAddResultMessage(): bool
    {
        return true;
    }
}
