<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Controller\Adminhtml\Sitemap;

use Infrangible\Sitemap\Block\Adminhtml\Sitemap\Grid\Container;
use Infrangible\Sitemap\Traits\Controller;

/**
 * The page with the list of sitemap files.
 */
class Index extends \Infrangible\BackendWidget\Controller\Backend\Object\Index
{
    use Controller;

    /**
     * Returns the container of the list, with the button to generate all sitemaps.
     *
     * @return string
     */
    protected function getGridBlockType(): string
    {
        return Container::class;
    }
}
