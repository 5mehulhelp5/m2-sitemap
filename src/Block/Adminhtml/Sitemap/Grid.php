<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Block\Adminhtml\Sitemap;

use Exception;
use Infrangible\Sitemap\Block\Adminhtml\Sitemap\Grid\Column\Renderer\Link;
use Infrangible\Sitemap\Block\Adminhtml\Sitemap\Grid\Column\Renderer\Stores;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * The list of sitemap files with their stores, date and link, and the action to generate a file.
 */
class Grid extends \Infrangible\BackendWidget\Block\Grid
{
    /**
     * The collection of the files needs no preparation.
     *
     * @param AbstractDb $collection
     *
     * @return void
     */
    protected function prepareCollection(AbstractDb $collection): void // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
    {
        // the collection reads the files of the configuration, it has no query to prepare
    }

    /**
     * Adds the columns.
     *
     * @return void
     * @throws Exception
     */
    protected function prepareFields(): void
    {
        $this->addTextColumn('filename', __('File Name')->render());
        $this->addTextColumnWithRenderer('store_ids', __('Stores')->render(), Stores::class);
        $this->addDatetimeColumn('created_at', __('Created At')->render());
        $this->addTextColumnWithRenderer('link', __('Link')->render(), Link::class);
    }

    /**
     * Returns the columns that are hidden by default: none.
     *
     * @return string[]
     */
    protected function getHiddenFieldNames(): array
    {
        return [];
    }

    /**
     * Adds the action to generate the file of the row.
     *
     * @return void
     * @throws Exception
     */
    protected function addActionColumn(): void
    {
        $this->addAction('generate', __('Generate')->render(), 'infrangible_sitemap/sitemap/generate', true);

        parent::addActionColumn();
    }
}
