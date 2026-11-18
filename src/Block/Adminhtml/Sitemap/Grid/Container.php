<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Block\Adminhtml\Sitemap\Grid;

/**
 * The container of the list of sitemap files, with the button to generate all sitemaps.
 */
class Container extends \Infrangible\BackendWidget\Block\Grid\Container
{
    /**
     * Adds the button to generate all sitemaps.
     *
     * @return void
     */
    public function _construct(): void
    {
        parent::_construct();

        $this->addButton(
            'generate',
            [
                'label'   => __('Generate All'),
                'onclick' => sprintf(
                    "confirmSetLocation('%s', '%s')",
                    $this->_escaper->escapeJs(__('Do you want to generate all sitemaps?')->render()),
                    $this->_escaper->escapeJs($this->_urlBuilder->getUrl('infrangible_sitemap/sitemap/all'))
                ),
                'class'   => 'add primary'
            ]
        );
    }
}
