<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Model;

use Exception;
use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Stores;
use Infrangible\Sitemap\Helper\Data;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * The sitemap files of the stores for the list in the admin: file name, stores, date of the file and link.
 */
class Collection extends \Magento\Framework\Data\Collection
{
    /**
     * @param EntityFactoryInterface $entityFactory
     * @param Variables $variables
     * @param Stores $storeHelper
     * @param Data $sitemapHelper
     * @param Writer $writer
     * @param File $file
     * @param LoggerInterface $logging
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        protected Variables $variables,
        protected Stores $storeHelper,
        protected Data $sitemapHelper,
        protected Writer $writer,
        protected File $file,
        protected LoggerInterface $logging
    ) {
        parent::__construct($entityFactory);
    }

    /**
     * Loads a row for every file name of the stores whose sitemap is generated.
     *
     * @param bool $printQuery
     * @param bool $logQuery
     *
     * @return $this
     * @throws Exception
     */
    public function loadData($printQuery = false, $logQuery = false): static
    {
        if ($this->isLoaded()) {
            return $this;
        }

        $storeIdsByFileName = [];

        foreach ($this->storeHelper->getStores() as $store) {
            $storeId = $this->variables->intValue($store->getId());

            if ($this->sitemapHelper->isEnabled($storeId)) {
                $storeIdsByFileName[ $this->sitemapHelper->getFileName($storeId) ][] = $storeId;
            }
        }

        foreach ($storeIdsByFileName as $fileName => $storeIds) {
            $modified = $this->getModifiedTime($fileName);
            $link = $modified === null ? '' : $this->sitemapHelper->getBaseUrl($storeIds[ 0 ]) . $fileName;

            $item = new DataObject();
            $item->setData(
                [
                    'id'         => $fileName,
                    'filename'   => $fileName,
                    'store_ids'  => implode(',', $storeIds),
                    'created_at' => $modified === null ? '' : gmdate('Y-m-d H:i:s', $modified),
                    'link'       => $link
                ]
            );

            $this->addItem($item);
        }

        $this->_setIsLoaded();

        return $this;
    }

    /**
     * Returns the time of the last change of the file, or null if it does not exist.
     *
     * @param string $fileName
     *
     * @return int|null
     */
    protected function getModifiedTime(string $fileName): ?int
    {
        try {
            $filePath = $this->writer->getFilePath($fileName);

            if (! $this->file->isFile($filePath)) {
                return null;
            }

            $modified = $this->file->stat($filePath)[ 'mtime' ] ?? null;

            return is_int($modified) ? $modified : null;
        } catch (Exception $exception) {
            $this->logging->error($exception->getMessage());

            return null;
        }
    }
}
