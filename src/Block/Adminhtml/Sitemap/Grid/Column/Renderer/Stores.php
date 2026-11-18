<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Block\Adminhtml\Sitemap\Grid\Column\Renderer;

use FeWeDev\Base\Variables;
use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Group;
use Magento\Store\Model\Website;

/**
 * Renders the stores of a sitemap file, grouped by website and store group.
 */
class Stores extends AbstractRenderer
{
    /**
     * @param Context $context
     * @param \Infrangible\Core\Helper\Stores $storeHelper
     * @param Variables $variables
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        protected \Infrangible\Core\Helper\Stores $storeHelper,
        protected Variables $variables,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Returns the websites, store groups and stores of the comma separated store IDs of the row.
     *
     * @param DataObject $row
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function render(DataObject $row): string
    {
        $index = $this->getColumn()->getData('index');
        $storeIds = explode(
            ',',
            is_string($index) ? $this->variables->stringValueOrDefault($row->getData($index)) : ''
        );

        $names = [];

        foreach ($this->storeHelper->getStores() as $store) {
            $storeGroup = $store->getGroup();
            $website = $storeGroup instanceof Group ? $storeGroup->getWebsite() : null;

            if ($storeGroup instanceof Group && $website instanceof Website &&
                in_array((string)$store->getId(), $storeIds, true)) {
                $names[ (string)$website->getName() ][ (string)$storeGroup->getName() ][] = (string)$store->getName();
            }
        }

        $result = '<div>';

        foreach ($names as $websiteName => $storeGroups) {
            $result .= $this->_escaper->escapeHtml($websiteName) . '<br/>';

            foreach ($storeGroups as $storeGroupName => $storeNames) {
                $result .= str_repeat('&nbsp;', 3) . $this->_escaper->escapeHtml($storeGroupName) . '<br/>';

                foreach ($storeNames as $storeName) {
                    $result .= str_repeat('&nbsp;', 6) . $this->_escaper->escapeHtml($storeName) . '<br/>';
                }
            }
        }

        return $result . '</div>';
    }
}
