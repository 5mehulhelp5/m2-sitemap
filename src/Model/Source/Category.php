<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Sitemap\Model\Source;

use Exception;
use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Export;
use Infrangible\Core\Helper\Stores;
use Infrangible\Sitemap\Helper\Data;
use Infrangible\Sitemap\Model\ISitemapUrl;
use Infrangible\Sitemap\Model\SitemapUrlFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;

/**
 * The active categories of the store.
 *
 * Events: infrangible_sitemap_attributes_category (attributes: data object with the attribute codes to load) and
 * infrangible_sitemap_transform_category (sitemap_url, category_data) for every URL.
 */
class Category implements ISource
{
    /**
     * @param Variables $variables
     * @param Database $databaseHelper
     * @param \Infrangible\Core\Helper\Category $categoryHelper
     * @param Export $exportHelper
     * @param Stores $storeHelper
     * @param Data $sitemapHelper
     * @param SitemapUrlFactory $sitemapUrlFactory
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        protected Variables $variables,
        protected Database $databaseHelper,
        protected \Infrangible\Core\Helper\Category $categoryHelper,
        protected Export $exportHelper,
        protected Stores $storeHelper,
        protected Data $sitemapHelper,
        protected SitemapUrlFactory $sitemapUrlFactory,
        protected ManagerInterface $eventManager
    ) {
    }

    /**
     * Returns the data of the active categories of the store by category ID.
     *
     * @param int $storeId
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     * @throws Exception
     */
    public function getStoreData(int $storeId): array
    {
        if (! $this->storeHelper->getStoreConfigFlag('infrangible_sitemap/category/export', true, $storeId)) {
            return [];
        }

        $data = new DataObject(['attributes' => ['url_path', 'updated_at', 'exclude_from_sitemap']]);

        $this->eventManager->dispatch('infrangible_sitemap_attributes_category', ['attributes' => $data]);

        $attributes = $data->getData('attributes');

        $dbAdapter = $this->databaseHelper->getDefaultConnection();

        $categoryIds = array_map(
            $this->variables->intValue(...),
            $this->categoryHelper->getActiveCategoryIds($dbAdapter, $storeId)
        );

        return $this->exportHelper->getCategoriesData(
            $dbAdapter,
            $categoryIds,
            $storeId,
            is_array($attributes) ? $attributes : []
        );
    }

    /**
     * Transforms the categories into sitemap URLs.
     *
     * @param int $storeId
     * @param array $storeData
     *
     * @phpstan-param array<int|string, mixed> $storeData
     *
     * @return ISitemapUrl[]
     */
    public function transformStoreData(int $storeId, array $storeData): array
    {
        $urls = [];

        foreach ($storeData as $categoryData) {
            if (! is_array($categoryData) ||
                $this->sitemapHelper->isExcluded($categoryData[ 'exclude_from_sitemap' ] ?? null)) {
                continue;
            }

            $sitemapUrl = $this->createSitemapUrlModel();

            $this->populateSitemapUrl($sitemapUrl, $storeId, $categoryData);

            $this->eventManager->dispatch(
                'infrangible_sitemap_transform_category',
                ['sitemap_url' => $sitemapUrl, 'category_data' => $categoryData]
            );

            if ($sitemapUrl->isValid()) {
                $urls[] = $sitemapUrl;
            }
        }

        return $urls;
    }

    /**
     * Returns the URL of the category: its URL rewrite, or its URL path, or an empty string.
     *
     * @param int $storeId
     * @param array $categoryData
     *
     * @phpstan-param array<int|string, mixed> $categoryData
     *
     * @return string
     */
    protected function getCategoryUrl(int $storeId, array $categoryData): string
    {
        $urlRewrites = $categoryData[ 'url_rewrites' ] ?? [];
        $path = is_array($urlRewrites) && count($urlRewrites) > 0 ?
            $this->variables->stringValueOrDefault(reset($urlRewrites)) : '';

        if ($path === '') {
            $path = $this->variables->stringValueOrDefault($categoryData[ 'url_path' ] ?? null);
        }

        return $path === '' ? '' : $this->sitemapHelper->getUrl($path, $storeId);
    }

    /**
     * Returns a new sitemap URL.
     *
     * @return ISitemapUrl
     */
    public function createSitemapUrlModel(): ISitemapUrl
    {
        return $this->sitemapUrlFactory->create();
    }

    /**
     * Sets the URL, the date of the last change, the change frequency and the priority of the category.
     *
     * @param ISitemapUrl $sitemapUrl
     * @param int $storeId
     * @param array $categoryData
     *
     * @phpstan-param array<int|string, mixed> $categoryData
     *
     * @return void
     */
    public function populateSitemapUrl(ISitemapUrl $sitemapUrl, int $storeId, array $categoryData): void
    {
        $sitemapUrl->setUrl($this->getCategoryUrl($storeId, $categoryData));
        $sitemapUrl->setLastModified($this->sitemapHelper->getLastModified($categoryData[ 'updated_at' ] ?? null));
        $sitemapUrl->setChangeFrequency($this->sitemapHelper->getChangeFrequency('category', $storeId, 'monthly'));
        $sitemapUrl->setPriority($this->sitemapHelper->getPriority('category', $storeId, '1.0'));
    }
}
