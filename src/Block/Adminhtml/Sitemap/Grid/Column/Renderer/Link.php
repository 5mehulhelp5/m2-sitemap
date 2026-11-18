<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Block\Adminhtml\Sitemap\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

/**
 * Renders the URL of a sitemap file as link that opens in a new window.
 */
class Link extends AbstractRenderer
{
    /**
     * Returns the link, or nothing if the file does not exist.
     *
     * @param DataObject $row
     *
     * @return string
     */
    public function render(DataObject $row): string
    {
        $index = $this->getColumn()->getData('index');
        $value = is_string($index) ? $row->getData($index) : null;

        if (! is_string($value) || $value === '') {
            return '';
        }

        return sprintf(
            '<a title="%s" href="%s" target="_blank" rel="noopener">%s</a>',
            $this->_escaper->escapeHtmlAttr(__('View')->render()),
            $this->_escaper->escapeUrl($value),
            $this->_escaper->escapeHtml($value)
        );
    }
}
